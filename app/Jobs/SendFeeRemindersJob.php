<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Http\Controllers\Finance\FeeReminderController;
use App\Services\SMSService;

class SendFeeRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $controller = new FeeReminderController(app(SMSService::class));
        $controller->sendAutomatedReminders();
    }
}
