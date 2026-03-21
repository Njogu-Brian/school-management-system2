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
        // Run synchronously so processing happens immediately when scheduler runs.
        // No queue worker required — critical for servers where queue:work may not be running.
        ProcessScheduledFeeCommunicationsJob::dispatchSync();
        $this->info('Scheduled fee communications processed.');

        return self::SUCCESS;
    }
}
