<?php

use App\Http\Controllers\BackupRestoreController;
use App\Jobs\SendFeeRemindersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Task Scheduling (Laravel 11+ loads this file; app/Console/Kernel.php is ignored)
|--------------------------------------------------------------------------
| Callback/job events MUST call name() before withoutOverlapping().
*/

Schedule::command('communications:send-scheduled')
    ->everyMinute()
    ->name('communications-send-scheduled')
    ->withoutOverlapping();

Schedule::command('fee-communications:process-scheduled')
    ->everyMinute()
    ->name('fee-communications-process-scheduled')
    ->withoutOverlapping();

// Bulk SMS jobs can run for hours; keep one worker and do not kill it after 5 minutes.
Schedule::command('queue:work --stop-when-empty --max-time=10800')
    ->everyMinute()
    ->name('queue-work')
    ->withoutOverlapping(200);

Schedule::job(new SendFeeRemindersJob)
    ->everyMinute()
    ->name('send-fee-reminders');

Schedule::command('sms:check-balance-alert')
    ->everyFifteenMinutes()
    ->name('sms-check-balance-alert')
    ->withoutOverlapping();

Schedule::command('system-alerts:escalate')
    ->everyFifteenMinutes()
    ->name('system-alerts-escalate')
    ->withoutOverlapping();

Schedule::command('academic:sync-current-term')
    ->dailyAt('00:05')
    ->name('academic-sync-current-term')
    ->withoutOverlapping();

Schedule::command('payment-plans:update-statuses')->dailyAt('00:15');

Schedule::command('parent-wallet:send-saving-reminders')
    ->everyFiveMinutes()
    ->name('parent-wallet-saving-reminders')
    ->withoutOverlapping();

Schedule::command('reminders:teacher-clock-attendance')
    ->dailyAt('09:00')
    ->weekdays()
    ->name('reminders-teacher-clock-attendance')
    ->withoutOverlapping();

Schedule::command('reminders:lesson-plans-upcoming --window=60')
    ->hourly()
    ->weekdays()
    ->name('reminders-lesson-plans-upcoming')
    ->withoutOverlapping();

Schedule::command('lesson-plans:recompute-pace --days=7 --threshold=0.6')
    ->dailyAt('17:30')
    ->weekdays()
    ->name('lesson-plans-recompute-pace')
    ->withoutOverlapping();

Schedule::command('fee-clearance:recompute')
    ->dailyAt('00:30')
    ->name('fee-clearance-recompute')
    ->withoutOverlapping();

Schedule::call([BackupRestoreController::class, 'runScheduledIfDue'])
    ->hourly()
    ->name('backup-run-scheduled-if-due')
    ->withoutOverlapping();

Schedule::command('backup:prune')->dailyAt('03:15');

Schedule::command('biotime:sync --pull')
    ->everyTwoMinutes()
    ->name('biotime-sync-pull')
    ->withoutOverlapping()
    ->when(fn () => filled(config('biotime.base_url')) && filled(config('biotime.password')));
