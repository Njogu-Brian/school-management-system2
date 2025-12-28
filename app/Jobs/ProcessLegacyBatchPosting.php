<?php

namespace App\Jobs;

use App\Services\LegacyLedgerPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessLegacyBatchPosting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $batchId, public ?string $classLabel = null)
    {
    }

    public function handle(LegacyLedgerPostingService $service): void
    {
        $service->processBatch($this->batchId, $this->classLabel);
    }
}

