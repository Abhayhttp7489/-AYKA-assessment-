<?php

namespace App\Repositories;

use App\Models\Product;

class ProductRepository
{
    public function findBySku(string $sku): ?Product
    {
        return Product::where('sku', $sku)->first();
    }

    /**
     * Upsert many products keyed by sku.
     */
    public function upsertMany(array $rows): void
    {
        $now = now();
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = array_merge($row, [
                'updated_at' => $now,
                'created_at' => $now,
            ]);
        }

        Product::upsert($payload, ['sku'], [
            'name', 'description', 'price', 'currency', 'stock', 'incoming_stock', 'supplier_name', 'updated_at'
        ]);
    }

    public function paginate(int $perPage = 20)
    {
        return Product::orderBy('name')->paginate($perPage);
    }
}