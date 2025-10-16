<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Throwable;

class SupplierProductSyncService
{
    /**
     * Sync supplier products from a CSV file.
     * Returns a result array with counts and errors.
     */
    public function syncFromCsv(string $path, string $delimiter = ','): array
    {
        $result = [
            'source' => $path,
            'total' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (!is_file($path)) {
            $msg = "CSV not found: {$path}";
            Log::error($msg);
            $result['errors'][] = $msg;
            return $result;
        }

        try {
            $rows = $this->readCsvAsCollection($path, $delimiter);
        } catch (Throwable $e) {
            $msg = 'Failed reading CSV: ' . $e->getMessage();
            Log::error($msg);
            $result['errors'][] = $msg;
            return $result;
        }

        [$normalized, $errors] = $this->normalizeAndValidateRows($rows);
        $result['errors'] = array_merge($result['errors'], $errors);
        $result['total'] = count($normalized) + $result['skipped'];

        // Aggregate duplicates in feed by SKU: last non-empty details win, incoming_stock sums.
        $aggregated = $this->aggregateBySku($normalized);

        // Determine inserted vs updated counts before upsert.
        $skus = array_keys($aggregated);
        $existing = Product::query()->whereIn('sku', $skus)->pluck('sku')->all();
        $result['inserted'] = count($skus) - count($existing);
        $result['updated'] = count($existing);

        // Upsert in bulk for efficiency.
        $now = now();
        $payload = [];
        foreach ($aggregated as $sku => $data) {
            $payload[] = array_merge($data, [
                'sku' => $sku,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        Product::upsert($payload, ['sku'], [
            'name', 'description', 'price', 'currency', 'stock', 'incoming_stock', 'supplier_name', 'updated_at'
        ]);

        return $result;
    }

    /**
     * Sync supplier products from an API endpoint returning JSON.
     * Expected schema: { products: [ { sku, name, ... } ] }
     */
    public function syncFromApi(string $url, ?string $token = null): array
    {
        $result = [
            'source' => $url,
            'total' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $req = Http::timeout(30)
                ->withHeaders(['Accept' => 'application/json'])
                ->retry(3, 500);
            if ($token) {
                $req = $req->withToken($token);
            }
            $response = $req->get($url);
        } catch (Throwable $e) {
            $msg = 'API call failed: ' . $e->getMessage();
            Log::error($msg);
            $result['errors'][] = $msg;
            return $result;
        }

        if (!$response->ok()) {
            $msg = 'API returned non-200: ' . $response->status();
            Log::error($msg);
            $result['errors'][] = $msg;
            return $result;
        }

        // Prefer JSON decoding; fall back to manual decode to handle BOM or non-standard responses.
        $data = $response->json();
        if ($data === null) {
            $raw = $response->body();
            if (is_string($raw) && str_starts_with($raw, "\xEF\xBB\xBF")) {
                $raw = substr($raw, 3);
            }
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            } else {
                $msg = 'API returned invalid JSON: ' . json_last_error_msg();
                Log::error($msg);
                $result['errors'][] = $msg;
                return $result;
            }
        }
        $products = Arr::get($data, 'products', []);
        $rows = collect($products);

        [$normalized, $errors] = $this->normalizeAndValidateRows($rows);
        $result['errors'] = array_merge($result['errors'], $errors);
        $result['total'] = count($normalized) + $result['skipped'];

        $aggregated = $this->aggregateBySku($normalized);
        $skus = array_keys($aggregated);
        $existing = Product::query()->whereIn('sku', $skus)->pluck('sku')->all();
        $result['inserted'] = count($skus) - count($existing);
        $result['updated'] = count($existing);

        $now = now();
        $payload = [];
        foreach ($aggregated as $sku => $data) {
            $payload[] = array_merge($data, [
                'sku' => $sku,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        Product::upsert($payload, ['sku'], [
            'name', 'description', 'price', 'currency', 'stock', 'incoming_stock', 'supplier_name', 'updated_at'
        ]);

        return $result;
    }

    /**
     * Read CSV into a LazyCollection of associative rows.
     */
    protected function readCsvAsCollection(string $path, string $delimiter = ','): LazyCollection
    {
        return LazyCollection::make(function () use ($path, $delimiter) {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new \RuntimeException('Unable to open CSV file: ' . $path);
            }
            $header = null;
            
            // Detect delimiter from the first line if the provided one likely doesn't match.
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                fclose($handle);
                throw new \RuntimeException('CSV appears empty: ' . $path);
            }
            $firstLine = $this->stripBom($firstLine);
            $detected = $this->detectDelimiterFromLine($firstLine, $delimiter);
            rewind($handle);

            try {
                // Read first row to decide whether it's a header or data
                $firstRow = fgetcsv($handle, 0, $detected);
                if ($firstRow === false) {
                    throw new \RuntimeException('CSV appears empty after delimiter detection: ' . $path);
                }

                // Normalize potential header values
                $normalizedHeaderCandidate = array_map(fn($h) => $this->normalizeHeaderValue($h), $firstRow);
                $hasLikelyHeader = $this->isLikelyHeader($normalizedHeaderCandidate);

                if ($hasLikelyHeader) {
                    $header = $normalizedHeaderCandidate;
                } else {
                    // No recognizable headers: fall back to positional columns
                    $header = [];
                    for ($i = 0; $i < count($firstRow); $i++) {
                        $header[$i] = 'col_' . $i;
                    }
                    // Yield the first row as data under positional keys
                    $assoc = [];
                    foreach ($header as $i => $key) {
                        $assoc[$key] = $firstRow[$i] ?? null;
                    }
                    yield $assoc;
                }

                // Stream remaining rows
                while (($row = fgetcsv($handle, 0, $detected)) !== false) {
                    $assoc = [];
                    foreach ($header as $i => $key) {
                        $assoc[$key] = $row[$i] ?? null;
                    }
                    yield $assoc;
                }
            } finally {
                fclose($handle);
            }
        });
    }

    /**
     * Normalize keys and validate rows. Returns [normalizedRows, errors].
     */
    protected function normalizeAndValidateRows($rows): array
    {
        $normalized = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $norm = $this->normalizeRow((array) $row);
            $validation = $this->validateRow($norm);

            if (!$validation['valid']) {
                $errors[] = 'Row ' . ($index + 1) . ': ' . implode('; ', $validation['errors']);
                Log::error(end($errors));
                continue;
            }

            $normalized[] = $validation['data'];
        }

        return [$normalized, $errors];
    }

    /**
     * Map various supplier keys to our canonical keys.
     */
    protected function normalizeRow(array $row): array
    {
        // Helper to get value by multiple possible keys.
        $val = function (array $keys, $default = null) use ($row) {
            foreach ($keys as $k) {
                if (array_key_exists($k, $row) && $row[$k] !== '' && $row[$k] !== null) {
                    return $row[$k];
                }
            }
            return $default;
        };

        // Include positional fallbacks (col_*) in addition to header-based keys
        $sku = $val(['sku', 'product_sku', 'sku_code', 'product_code', 'item_sku', 'item_code', 'code', 'id', 'product_id', 'item_id', 'customer_id', 'col_0']);
        $name = $val(['name', 'product_name', 'product_title', 'title', 'item_name', 'company', 'col_1']);
        $desc = $val(['description', 'desc', 'Description', 'col_2']);
        $price = $val(['price', 'Price', 'col_3'], null);
        $currencyRaw = $val(['currency', 'Currency', 'col_4'], 'USD');
        $currency = $this->sanitizeCurrency($currencyRaw);
        $stock = (int) ($val(['stock', 'Stock', 'available_stock', 'col_5'], 0) ?? 0);
        $incoming = (int) ($val(['incoming_stock', 'incoming', 'incoming_qty', 'Incoming', 'incoming qty', 'col_6'], 0) ?? 0);
        $supplier = $val(['supplier_name', 'supplier', 'Supplier', 'col_7'], null);

        // If description missing but person-like fields exist, compose a note
        if (($desc === null || $desc === '') && ($val(['first_name', 'firstname'], null) || $val(['last_name', 'lastname'], null) || $val(['city'], null) || $val(['country'], null))) {
            $parts = [];
            $fn = $val(['first_name', 'firstname'], null);
            $ln = $val(['last_name', 'lastname'], null);
            $city = $val(['city'], null);
            $country = $val(['country'], null);
            $namePart = trim(implode(' ', array_filter([$fn, $ln], fn($x) => $x)));
            if ($namePart !== '') { $parts[] = $namePart; }
            $loc = trim(implode(', ', array_filter([$city, $country], fn($x) => $x)));
            if ($loc !== '') { $parts[] = $loc; }
            if (!empty($parts)) { $desc = implode(' — ', $parts); }
        }

        return [
            'sku' => is_string($sku) ? trim($sku) : $sku,
            'name' => is_string($name) ? trim($name) : $name,
            'description' => is_string($desc) ? trim($desc) : $desc,
            'price' => $price !== null ? (float) $price : null,
            'currency' => $currency ?: 'USD',
            'stock' => $stock,
            'incoming_stock' => $incoming,
            'supplier_name' => $supplier ? trim((string) $supplier) : null,
        ];
    }

    /**
     * Basic validation per row.
     */
    protected function validateRow(array $row): array
    {
        $errors = [];
        if (!isset($row['sku']) || $row['sku'] === null || trim((string) $row['sku']) === '') {
            $errors[] = 'Missing required field: sku';
        }
        if (!isset($row['name']) || trim((string) $row['name']) === '') {
            $errors[] = 'Missing required field: name';
        }
        if (isset($row['price']) && $row['price'] !== null && !is_numeric($row['price'])) {
            $errors[] = 'Invalid price value';
        }
        if (isset($row['incoming_stock']) && !is_int($row['incoming_stock'])) {
            $row['incoming_stock'] = (int) $row['incoming_stock'];
        }
        if (isset($row['stock']) && !is_int($row['stock'])) {
            $row['stock'] = (int) $row['stock'];
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'data' => $row,
        ];
    }

    /**
     * Aggregate duplicate SKUs: last non-empty details win; incoming_stock sums.
     */
    protected function aggregateBySku(array $rows): array
    {
        $agg = [];
        foreach ($rows as $r) {
            $sku = $r['sku'];
            if (!isset($agg[$sku])) {
                $agg[$sku] = $r;
                continue;
            }
            // Merge non-empty values
            foreach (['name', 'description', 'price', 'currency', 'stock', 'supplier_name'] as $key) {
                $current = $agg[$sku][$key] ?? null;
                $incoming = $r[$key] ?? null;
                if ($incoming !== null && $incoming !== '' && (!($current !== null && $current !== '') || $key === 'stock')) {
                    $agg[$sku][$key] = $incoming;
                }
            }
            // Sum incoming_stock
            $agg[$sku]['incoming_stock'] = ($agg[$sku]['incoming_stock'] ?? 0) + ($r['incoming_stock'] ?? 0);
        }
        return $agg;
    }

    /**
     * Detect delimiter by counting occurrences in the header line.
     */
    protected function detectDelimiterFromLine(string $line, string $default = ','): string
    {
        $candidates = [',', ';', "\t", '|'];
        $counts = [];
        foreach ($candidates as $d) {
            $counts[$d] = substr_count($line, $d);
        }
        arsort($counts);
        $top = array_key_first($counts);
        if ($top !== null && $counts[$top] > 0) {
            return $top;
        }
        return $default;
    }

    /**
     * Normalize header strings: strip BOM, trim, replace spaces/dashes/dots with underscore, lower-case.
     */
    protected function normalizeHeaderValue(string $h): string
    {
        $clean = $this->stripBom($h);
        $clean = trim($clean);
        $clean = str_replace([' ', '-', '.'], '_', $clean);
        // Remove any remaining non-alphanumeric/underscore characters (e.g., parentheses, asterisks)
        $clean = preg_replace('/[^A-Za-z0-9_]/', '', $clean);
        return Str::lower($clean);
    }

    /**
     * Decide if a row likely represents a header based on presence of key synonyms.
     */
    protected function isLikelyHeader(array $normalizedCells): bool
    {
        $skuSynonyms = ['sku', 'product_sku', 'sku_code', 'product_code', 'item_sku', 'item_code', 'code', 'id', 'product_id', 'item_id'];
        $nameSynonyms = ['name', 'product_name', 'product_title', 'title', 'item_name'];
        $genericHeaderTokens = ['company', 'first_name', 'firstname', 'last_name', 'lastname', 'customer_id', 'index', 'phone', 'phone_1', 'phone1', 'email', 'address', 'city', 'state', 'zip', 'country'];
        $set = array_flip(array_merge($skuSynonyms, $nameSynonyms, $genericHeaderTokens));
        foreach ($normalizedCells as $cell) {
            if (isset($set[$cell])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sanitize currency to a valid 3-letter ISO code or default to USD.
     */
    protected function sanitizeCurrency($value): string
    {
        $codes = [
            'USD','EUR','GBP','NGN','AED','AUD','CAD','CHF','CNY','JPY','INR','ZAR','BRL','MXN','SEK','NOK','DKK',
            'RUB','PLN','TRY','SAR','KWD','QAR','EGP','KES','SGD','HKD','NZD','UAH','RON','HUF','CZK','MAD','TND'
        ];
        $s = is_string($value) ? strtoupper(trim($value)) : '';
        if (preg_match('/^[A-Z]{3}$/', $s) && in_array($s, $codes, true)) {
            return $s;
        }
        // Try to find a 3-letter code inside the string
        if (preg_match('/[A-Z]{3}/', $s, $m)) {
            $candidate = $m[0];
            if (in_array($candidate, $codes, true)) {
                return $candidate;
            }
        }
        return 'USD';
    }

    /**
     * Strip UTF-8 BOM if present.
     */
    protected function stripBom(string $s): string
    {
        return str_starts_with($s, "\xEF\xBB\xBF") ? substr($s, 3) : $s;
    }
}