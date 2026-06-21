<?php

namespace App\Jobs\Website;

use App\Services\Website\FeeReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOverdueFeeRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $channels = ['sms', 'email']
    ) {}

    public function handle(FeeReminderService $service): void
    {
        $service->sendOverdueReminders($this->channels);
    }
}
