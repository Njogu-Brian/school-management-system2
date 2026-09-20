<?php

namespace App\Jobs;

use App\Http\Controllers\Finance\FeeReminderController;
use App\Services\CommunicationPauseService;
use App\Services\FeeReminderAutomationSettings;
use App\Services\SMSService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SendFeeRemindersJob implements ShouldQueue
{
    use Queueable;

    /**
     * Whether this is the minute the reminders are configured to go out.
     *
     * The scheduler checks this before dispatching, so the job is only queued on
     * the one minute a day it can do work rather than all 1,440 — each dispatch
     * otherwise cost a queue insert, a worker pickup and a full framework boot to
     * immediately return. handle() re-checks it so a manual dispatch still can't
     * send at the wrong time.
     */
    public static function shouldRunNow(): bool
    {
        if (CommunicationPauseService::isPaused()) {
            return false;
        }

        $cfg = FeeReminderAutomationSettings::load();
        if (!$cfg->enabled) {
            return false;
        }

        $now = now();
        [$wantH, $wantM] = array_map('intval', explode(':', $cfg->sendTime));

        return (int) $now->format('H') === $wantH && (int) $now->format('i') === $wantM;
    }

    public function handle(): void
    {
        if (!self::shouldRunNow()) {
            return;
        }

        if (!Cache::add('fee_reminders_daily_fire_' . now()->toDateString(), 1, 86400)) {
            return;
        }

        $controller = new FeeReminderController(app(SMSService::class));
        $controller->sendAutomatedReminders();
    }
}
