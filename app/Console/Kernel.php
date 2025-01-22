<?php

namespace App\Console;

use App\Jobs\SynchronizeJob;
use App\Models\ProductSynchronization;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        ProductSynchronization::ordered()->get()
            ->each(function ($sync) use ($schedule) {
                $schedule->job(new SynchronizeJob($sync->supplier_name))
                    ->cron(env("APP_ENV") == "local"
                        ? "* * * * *"
                        : "*/2 * * * *"
                    );
            });

        $schedule->command("backup:clean")->cron("0 0 * * *");
        $schedule->command("backup:run")->cron("15 0 * * *");
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
