<?php

namespace App\Console\Commands;

use App\Services\Website\CampaignAutomationService;
use Illuminate\Console\Command;

class SendAbandonedAdmissionReminders extends Command
{
    protected $signature = 'website:admission-reminders';

    protected $description = 'Send reminder emails for incomplete website admission applications';

    public function handle(CampaignAutomationService $automation): int
    {
        $sent = $automation->sendAbandonedAdmissionReminders();
        $this->info("Sent {$sent} abandoned admission reminder(s).");

        return self::SUCCESS;
    }
}
