<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSupplierFeed;
use Illuminate\Console\Command;

class SupplierSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'supplier:sync {--csv=} {--api=} {--token=}';

    /**
     * The console command description.
     */
    protected $description = 'Sync supplier products via CSV or API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $csv = $this->option('csv');
        $api = $this->option('api');
        $token = $this->option('token');

        if (!$csv && !$api) {
            $this->error('Provide either --csv=path or --api=url');
            return self::FAILURE;
        }
        if ($csv && $api) {
            $this->error('Provide only one source: --csv or --api');
            return self::FAILURE;
        }

        if ($csv) {
            ProcessSupplierFeed::dispatch($type = 'csv', $csv);
            $this->info('Queued CSV sync job for: ' . $csv);
        }
        if ($api) {
            ProcessSupplierFeed::dispatch($type = 'api', $api, $token);
            $this->info('Queued API sync job for: ' . $api);
        }

        return self::SUCCESS;
    }
}