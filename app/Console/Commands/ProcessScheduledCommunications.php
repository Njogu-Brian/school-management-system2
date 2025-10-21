<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendScheduledCommunicationsJob;

class ProcessScheduledCommunications extends Command
{
    protected $signature = 'communication:process-scheduled';
    protected $description = 'Process and send all scheduled communications';

    public function handle()
    {
        dispatch(new SendScheduledCommunicationsJob());
        $this->info('Scheduled communications job dispatched successfully.');
    }
}
