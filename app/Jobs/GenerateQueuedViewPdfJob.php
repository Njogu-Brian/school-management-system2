<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Pdf\ChunkedDompdfExporter;
use App\Services\Pdf\PdfExportDeliveryService;
use App\Services\PDFExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Generic queued DomPDF export for list/table views (directory, enrollment, etc.).
 */
class GenerateQueuedViewPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $sharedData  JSON-serializable view data (no Eloquent models / closures)
     * @param  list<mixed>  $items
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public int $userId,
        public string $view,
        public array $sharedData,
        public array $items,
        public array $options = [],
        public string $title = 'PDF export',
        public string $module = 'general',
    ) {
        $this->onQueue('default');
    }

    public function handle(
        ChunkedDompdfExporter $exporter,
        PdfExportDeliveryService $delivery,
        PDFExportService $pdfService,
    ): void {
        @ini_set('memory_limit', (string) config('pdf_export.memory_limit', '512M'));
        @ini_set('max_execution_time', '0');
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $user = User::find($this->userId);
        if ($user) {
            Auth::setUser($user);
        }

        $chunkKey = $this->options['chunk_data_key'] ?? 'rows';
        $paper = $this->options['paper_size'] ?? 'A4';
        $orientation = $this->options['orientation'] ?? 'portrait';
        $filename = $this->options['filename'] ?? ('export-'.now()->format('Ymd-His').'.pdf');
        $chunkSize = max(1, (int) ($this->options['chunk_size'] ?? config('pdf_export.chunk_size', 15)));
        $branding = $pdfService->brandingForPdf();
        $shared = array_merge($this->sharedData, [
            'branding' => $branding,
            'generated_at' => $this->sharedData['generated_at'] ?? now()->format('Y-m-d H:i:s'),
            'generated_by' => $this->sharedData['generated_by']
                ?? ($user?->first_name ?? $user?->name ?? 'System'),
        ]);

        $built = $exporter->build(collect($this->items), function (Collection $chunk) use (
            $exporter,
            $chunkKey,
            $paper,
            $orientation,
            $shared
        ) {
            $data = $shared;
            $data[$chunkKey] = $chunk->values()->all();

            return $exporter->renderView($this->view, $data, $paper, $orientation);
        }, [
            'filename' => $filename,
            'chunk_size' => $chunkSize,
            'allow_over_sync' => true,
            'hard_max' => (int) ($this->options['hard_max'] ?? config('pdf_export.hard_max_items', 2000)),
        ]);

        try {
            $delivery->storeAndNotify(
                $this->userId,
                $built['path'],
                $built['filename'] ?? $filename,
                $this->title,
                $this->module,
                count($this->items),
                [
                    'view' => $this->view,
                    'merged' => $built['merged'] ?? null,
                ],
                $built['mime'] ?? null,
            );
        } finally {
            @unlink($built['path']);
            if (! empty($built['cleanup_dir']) && is_dir($built['cleanup_dir'])) {
                foreach (scandir($built['cleanup_dir']) ?: [] as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }
                    @unlink($built['cleanup_dir'].DIRECTORY_SEPARATOR.$item);
                }
                @rmdir($built['cleanup_dir']);
            }
        }

        Log::info('GenerateQueuedViewPdfJob finished', [
            'user_id' => $this->userId,
            'module' => $this->module,
            'items' => count($this->items),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateQueuedViewPdfJob failed', [
            'user_id' => $this->userId,
            'module' => $this->module,
            'error' => $exception->getMessage(),
        ]);
    }
}
