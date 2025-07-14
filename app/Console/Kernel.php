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
        // Send session reminders every hour
        $schedule->command('sessions:send-reminders')->hourly();
        
        // Send scheduled notifications every minute
        $schedule->command('notifications:send-scheduled')
            ->everyMinute()
            ->appendOutputTo(storage_path('logs/scheduler.log'))
            ->withoutOverlapping();
        
        // Process scheduled ticket responses every minute
        $schedule->command('app:process-scheduled-responses')->everyMinute();
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