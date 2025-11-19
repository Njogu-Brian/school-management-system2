<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

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
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
