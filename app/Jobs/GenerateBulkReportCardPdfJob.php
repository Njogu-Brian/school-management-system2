<?php

namespace App\Jobs;

use App\Services\Academics\ReportCardBulkPdfExporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateBulkReportCardPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public array $filters,
        public int $userId,
    ) {
        $this->onQueue('default');
    }

    public function handle(ReportCardBulkPdfExporter $exporter): void
    {
        @ini_set('memory_limit', (string) config('pdf_export.memory_limit', '512M'));
        @ini_set('max_execution_time', '0');
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $path = $exporter->buildQueuedClassBatchZip($this->filters, $this->userId);

        Log::info('GenerateBulkReportCardPdfJob finished', [
            'user_id' => $this->userId,
            'path' => $path,
            'filters' => $this->filters,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateBulkReportCardPdfJob failed', [
            'user_id' => $this->userId,
            'filters' => $this->filters,
            'error' => $exception->getMessage(),
        ]);
    }
}
