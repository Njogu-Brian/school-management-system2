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
        \App\Console\Commands\PurgeLocalStorageAndDocuments::class,
        \App\Console\Commands\RecomputeFeeClearances::class,
        \App\Console\Commands\SendTeacherClockAttendanceReminders::class,
        \App\Console\Commands\SendUpcomingLessonPlanReminders::class,
        \App\Console\Commands\RecomputeLessonPlanPace::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Runs every minute — ensure Laravel's scheduler is active via cron or `php artisan schedule:work`
        $schedule->command('communications:send-scheduled')->everyMinute();
        $schedule->command('fee-communications:process-scheduled')->everyMinute();

        // Process queued jobs (SMS, Email, WhatsApp bulk sends) — runs automatically every minute
        // Bulk SMS jobs can run for a long time, so do not kill the worker early.
        // Also prevent overlaps so only one worker runs at a time.
        $schedule->command('queue:work --stop-when-empty --max-time=10800')
            ->everyMinute()
            ->withoutOverlapping();
        
        // Fee reminders: job runs every minute and fires once per day at configured time (see Fee reminder automation settings)
        $schedule->job(new \App\Jobs\SendFeeRemindersJob)->everyMinute();

        // Daily 9am reminder to teachers missing clock-in/attendance (school days only).
        $schedule->command('reminders:teacher-clock-attendance')
            ->dailyAt('09:00')
            ->weekdays()
            ->withoutOverlapping();

        // Upcoming lesson plan reminders (weekdays; prevents overlap/spam via cache).
        $schedule->command('reminders:lesson-plans-upcoming --window=60')
            ->hourly()
            ->weekdays()
            ->withoutOverlapping();

        // Daily pace/consistency checks for lesson plans (weekdays).
        $schedule->command('lesson-plans:recompute-pace --days=7 --threshold=0.6')
            ->dailyAt('17:30')
            ->weekdays()
            ->withoutOverlapping();

        // Update payment plan statuses (overdue, completed, broken) daily
        $schedule->command('payment-plans:update-statuses')->dailyAt('00:15');

        // Recompute fee clearance snapshots daily (for gate/class/transport enforcement)
        $schedule->command('fee-clearance:recompute')->dailyAt('00:30')->withoutOverlapping();

        // Database backup schedule checker (honors frequency/time in settings)
        $schedule->call(function () {
            BackupRestoreController::runScheduledIfDue();
        })->hourly();

        // Remove local DB dumps older than BACKUP_RETENTION_DAYS (default 5)
        $schedule->command('backup:prune')->dailyAt('03:15');
    }

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
