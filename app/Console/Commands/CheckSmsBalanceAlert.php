<?php

namespace App\Console\Commands;

use App\Services\SmsBalanceMonitorService;
use Illuminate\Console\Command;

class CheckSmsBalanceAlert extends Command
{
    protected $signature = 'sms:check-balance-alert';

    protected $description = 'Check SMS credit balance and raise low-balance alerts for Super Admins';

    public function handle(SmsBalanceMonitorService $monitor): int
    {
        $monitor->checkAndAlert();

        $this->info('SMS balance check completed.');

        return self::SUCCESS;
    }
}
