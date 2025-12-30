<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SendScheduledCommunicationsJob;

class SendScheduledCommunications extends Command
{
    protected $signature = 'communications:send-scheduled';
    protected $description = 'Send all due scheduled communications';

    public function handle()
    {
        dispatch(new SendScheduledCommunicationsJob());
        $this->info('Scheduled communications job dispatched successfully.');

        return self::SUCCESS;
    }
}
