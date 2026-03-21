<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessScheduledFeeCommunicationsJob;

class ProcessScheduledFeeCommunications extends Command
{
    protected $signature = 'fee-communications:process-scheduled';
    protected $description = 'Process and send due scheduled fee communications';

    public function handle()
    {
        dispatch(new ProcessScheduledFeeCommunicationsJob());
        $this->info('Scheduled fee communications job dispatched.');

        return self::SUCCESS;
    }
}
