<?php

namespace App\Jobs;

use App\Models\ParentInfo;
use App\Services\ParentCredentialsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendParentCredentialsBulkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<int>  $parentInfoIds
     * @param  list<string>  $channels
     */
    public function __construct(
        public array $parentInfoIds,
        public array $channels,
        public ?int $requestedBy = null,
    ) {
    }

    public function handle(ParentCredentialsService $credentials): void
    {
        foreach (array_unique($this->parentInfoIds) as $pid) {
            try {
                $parent = ParentInfo::find($pid);
                if (! $parent) {
                    continue;
                }
                $credentials->provisionAndShare($parent, $this->channels);
            } catch (\Throwable $e) {
                Log::warning('SendParentCredentialsBulkJob failed', [
                    'parent_info_id' => $pid,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
