<?php

namespace App\Console\Commands;

use App\Services\SystemAlertService;
use Illuminate\Console\Command;

class EscalateSystemAlerts extends Command
{
    protected $signature = 'system-alerts:escalate {--minutes=30 : Minutes before unacknowledged alerts are re-escalated}';

    protected $description = 'Re-send email/SMS escalation for unacknowledged Super Admin alerts';

    public function handle(SystemAlertService $alerts): int
    {
        $count = $alerts->escalateUnacknowledgedAlerts((int) $this->option('minutes'));
        $this->info("Escalated {$count} alert group(s).");

        return self::SUCCESS;
    }
}
