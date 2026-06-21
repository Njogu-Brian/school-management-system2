<?php

namespace App\Jobs\Website;

use App\Models\Website\AiContentLog;
use App\Services\Website\SchoolAiContentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAiContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $logId
    ) {}

    public function handle(SchoolAiContentService $service): void
    {
        $log = AiContentLog::find($this->logId);
        if ($log) {
            $service->completeLog($log);
        }
    }
}
