<?php

namespace App\Console\Commands;

use App\Services\SmsDeferredMessageService;
use Illuminate\Console\Command;

class ProcessDeferredSmsCommand extends Command
{
    protected $signature = 'sms:process-deferred';

    protected $description = 'Purge expired deferred SMS (e.g. attendance after 16:00) and flush pending when credits allow';

    public function handle(SmsDeferredMessageService $service): int
    {
        $expired = $service->purgeExpired();
        $flush = $service->flushPending();

        $this->info("Expired: {$expired}; sent: {$flush['sent']}; skipped: {$flush['skipped']}; paused: " . ($flush['paused'] ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
