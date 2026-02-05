<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\BackupRestoreController;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SendScheduledCommunications::class,
        \App\Console\Commands\BackfillStudentDiaries::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Runs every minute — ensure Laravel's scheduler is active via cron or `php artisan schedule:work`
        $schedule->command('communications:send-scheduled')->everyMinute();
        
        // Send fee reminders daily at 9 AM
        $schedule->job(new \App\Jobs\SendFeeRemindersJob)->dailyAt('09:00');

        // Update payment plan statuses (overdue, completed, broken) daily
        $schedule->command('payment-plans:update-statuses')->dailyAt('00:15');

        // Database backup schedule checker (honors frequency/time in settings)
        $schedule->call(function () {
            BackupRestoreController::runScheduledIfDue();
        })->hourly();
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
