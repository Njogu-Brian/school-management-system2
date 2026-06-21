<?php

namespace App\Console\Commands;

use App\Jobs\Website\ComputeExecutiveAlertsJob;
use App\Jobs\Website\SendOverdueFeeRemindersJob;
use Illuminate\Console\Command;

class WebsiteEnterpriseMaintenanceCommand extends Command
{
    protected $signature = 'website:enterprise-maintenance {--reminders : Send overdue fee reminders} {--alerts : Compute executive alerts}';

    protected $description = 'Run website enterprise layer maintenance (fee reminders, executive alerts)';

    public function handle(): int
    {
        if ($this->option('reminders') || ! $this->option('alerts')) {
            SendOverdueFeeRemindersJob::dispatch(['sms', 'email', 'whatsapp']);
            $this->info('Overdue fee reminders queued.');
        }

        if ($this->option('alerts') || ! $this->option('reminders')) {
            ComputeExecutiveAlertsJob::dispatch();
            $this->info('Executive alerts computation queued.');
        }

        return self::SUCCESS;
    }
}
