<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $csv = env('SUPPLIER_CSV_PATH');
        $api = env('SUPPLIER_API_URL');
        $token = env('SUPPLIER_API_TOKEN');
        if ($csv) {
            $schedule->command("supplier:sync --csv=\"{$csv}\"")->dailyAt('02:00');
        }
        if ($api) {
            $tokenArg = $token ? " --token=\"{$token}\"" : '';
            $schedule->command("supplier:sync --api=\"{$api}\"{$tokenArg}")->dailyAt('02:00');
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}