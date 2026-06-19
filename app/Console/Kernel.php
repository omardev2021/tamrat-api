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
        $schedule->command('retention:reorder-reminders')
                 ->timezone('Asia/Riyadh')->dailyAt('11:00');

        $schedule->command('retention:review-requests')
                 ->timezone('Asia/Riyadh')->dailyAt('11:30');

        $schedule->command('retention:win-back')
                 ->timezone('Asia/Riyadh')->dailyAt('12:00');

        $schedule->command('retention:abandoned-cart')
                 ->hourly();

        // WhatsApp predictive-replenishment + occasion nudges (gated: only sends
        // when services.lifecycle.wa_enabled + an approved template are set).
        $schedule->command('lifecycle:whatsapp --cooldown=21')
                 ->timezone('Asia/Riyadh')->dailyAt('10:30');
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
