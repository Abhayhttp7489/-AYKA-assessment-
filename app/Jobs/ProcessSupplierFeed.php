<?php

namespace App\Jobs;

use App\Services\SupplierProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSupplierFeed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var string csv|api */
    public string $type;
    public string $source;
    public ?string $token;

    /**
     * Create a new job instance.
     */
    public function __construct(string $type, string $source, ?string $token = null)
    {
        $this->type = $type;
        $this->source = $source;
        $this->token = $token;
    }

    /**
     * Execute the job.
     */
    public function handle(SupplierProductSyncService $service): void
    {
        if ($this->type === 'csv') {
            $result = $service->syncFromCsv($this->source);
        } else {
            $result = $service->syncFromApi($this->source, $this->token);
        }

        Log::info('Supplier sync completed', $result);
    }
}