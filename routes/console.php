<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Task Scheduling (Laravel 11+ uses routes/console.php; Kernel.php is ignored)
|--------------------------------------------------------------------------
*/
Schedule::command('communications:send-scheduled')->everyMinute();
Schedule::command('fee-communications:process-scheduled')->everyMinute();
Schedule::command('queue:work --stop-when-empty --max-time=300')->everyMinute();
Schedule::job(new \App\Jobs\SendFeeRemindersJob)->dailyAt('09:00');
Schedule::command('payment-plans:update-statuses')->dailyAt('00:15');
Schedule::call([\App\Http\Controllers\BackupRestoreController::class, 'runScheduledIfDue'])->hourly();
