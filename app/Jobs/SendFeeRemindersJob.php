<?php

namespace App\Jobs;

use App\Http\Controllers\Finance\FeeReminderController;
use App\Services\FeeReminderAutomationSettings;
use App\Services\SMSService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SendFeeRemindersJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $cfg = FeeReminderAutomationSettings::load();
        if (!$cfg->enabled) {
            return;
        }

        $now = now();
        [$wantH, $wantM] = array_map('intval', explode(':', $cfg->sendTime));
        if ((int) $now->format('H') !== $wantH || (int) $now->format('i') !== $wantM) {
            return;
        }

        if (!Cache::add('fee_reminders_daily_fire_' . $now->toDateString(), 1, 86400)) {
            return;
        }

        $controller = new FeeReminderController(app(SMSService::class));
        $controller->sendAutomatedReminders();
    }
}
