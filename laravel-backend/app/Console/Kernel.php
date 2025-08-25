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
        // Send daily meeting notifications at configured time (default 07:00)
        $schedule->command('meeting:send-daily-notifications')
                 ->dailyAt('07:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/daily-notifications.log'));

        // Check for meeting reminders every minute
        $schedule->command('meeting:send-reminders')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/meeting-reminders.log'));

        // Test WhatsApp connection daily at 6:00 AM
        $schedule->command('whatsapp:test-connection')
                 ->dailyAt('06:00')
                 ->appendOutputTo(storage_path('logs/whatsapp-connection.log'));

        // Clean up old notifications weekly (keep 30 days)
        $schedule->command('meeting:cleanup-notifications --days=30')
                 ->weekly()
                 ->sundays()
                 ->at('02:00')
                 ->appendOutputTo(storage_path('logs/cleanup.log'));

        // Generate monthly reports on the 1st of each month
        $schedule->command('meeting:generate-reports --period=monthly --format=json')
                 ->monthlyOn(1, '03:00')
                 ->appendOutputTo(storage_path('logs/reports.log'));

        // Clean up old log files (keep 14 days)
        $schedule->command('log:clear --days=14')
                 ->weekly()
                 ->sundays()
                 ->at('01:00');

        // Optimize application cache weekly
        $schedule->command('optimize:clear')
                 ->weekly()
                 ->sundays()
                 ->at('04:00');

        // Backup database daily at 1:00 AM (if backup package is installed)
        // $schedule->command('backup:run')
        //          ->dailyAt('01:00')
        //          ->appendOutputTo(storage_path('logs/backup.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     */
    protected function scheduleTimezone(): string
    {
        return config('app.timezone', 'UTC');
    }
}