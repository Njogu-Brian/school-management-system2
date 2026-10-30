<?php

namespace App\Jobs;

use App\Services\InvoicePublishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Publish draft fee invoices and notify parents off the HTTP request
 * so Cloudflare / proxy timeouts (524) do not interrupt large batches.
 */
class PublishInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Match supervisor `queue:work --timeout=900`. */ 
    public int $timeout = 900;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, string>  $channels
     */
    public function __construct(
        public array $filters,
        public array $channels,
        public bool $notify,
        public int $userId,
        public int $pendingCount = 0,
    ) {
        $this->onQueue('default');
    }

    public function handle(InvoicePublishService $service): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        Log::info('PublishInvoicesJob started', [
            'user_id' => $this->userId,
            'pending' => $this->pendingCount,
            'filters' => $this->filters,
            'channels' => $this->channels,
            'notify' => $this->notify,
        ]);

        $result = $service->publishByFilters(
            $this->filters,
            $this->channels,
            $this->notify,
            false,
            $this->userId
        );

        Log::info('PublishInvoicesJob finished', [
            'user_id' => $this->userId,
            'filters' => $this->filters,
            'result' => $result,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishInvoicesJob failed', [
            'user_id' => $this->userId,
            'filters' => $this->filters,
            'error' => $exception->getMessage(),
        ]);
    }
}
