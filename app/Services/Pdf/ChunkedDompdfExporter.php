<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Ground-rule exporter: never feed DomPDF a giant multi-doc HTML tree.
 * Chunk → render → release memory → merge (or ZIP).
 */
class ChunkedDompdfExporter
{
    public function __construct(
        protected ?PdfBinaryMerger $merger = null,
    ) {
        $this->merger ??= new PdfBinaryMerger();
    }

    /**
     * @param  iterable<int, mixed>  $items
     * @param  callable(Collection $chunk, int $chunkIndex): \Barryvdh\DomPDF\PDF|string  $renderChunk
     *         Must return a DomPDF instance or raw PDF binary string for that chunk only.
     * @param  array{
     *   filename?: string,
     *   chunk_size?: int,
     *   sync_max?: int,
     *   hard_max?: int,
     *   disposition?: 'inline'|'attachment',
     *   paper?: string|array,
     *   orientation?: string,
     * }  $options
     */
    public function download(iterable $items, callable $renderChunk, array $options = []): BinaryFileResponse|Response
    {
        return $this->respond($items, $renderChunk, $options, $options['disposition'] ?? 'attachment');
    }

    /**
     * @param  iterable<int, mixed>  $items
     * @param  callable(Collection $chunk, int $chunkIndex): \Barryvdh\DomPDF\PDF|string  $renderChunk
     */
    public function stream(iterable $items, callable $renderChunk, array $options = []): BinaryFileResponse|Response
    {
        return $this->respond($items, $renderChunk, $options, 'inline');
    }

    /**
     * Build merged file on disk; caller owns cleanup if not using respond().
     *
     * @param  iterable<int, mixed>  $items
     * @param  callable(Collection $chunk, int $chunkIndex): \Barryvdh\DomPDF\PDF|string  $renderChunk
     * @return array{path: string, mime: string, extension: string, merged: bool, filename: string, item_count: int}
     */
    public function build(iterable $items, callable $renderChunk, array $options = []): array
    {
        $this->applyRuntimeLimits();

        $collection = $items instanceof Collection ? $items->values() : Collection::make($items)->values();
        $count = $collection->count();
        $hardMax = (int) ($options['hard_max'] ?? config('pdf_export.hard_max_items', 500));
        $syncMax = (int) ($options['sync_max'] ?? config('pdf_export.sync_max_items', 150));

        if ($count === 0) {
            throw new \InvalidArgumentException('No items to export as PDF.');
        }

        if ($count > $hardMax) {
            throw new \RuntimeException(
                "Too many items for PDF export ({$count}). Maximum is {$hardMax}. Narrow your filters."
            );
        }

        if ($count > $syncMax && empty($options['allow_over_sync'])) {
            throw new \RuntimeException(
                "Too many items for a synchronous PDF ({$count}). Maximum is {$syncMax}. Narrow filters or run a queued export."
            );
        }

        $chunkSize = max(1, (int) ($options['chunk_size'] ?? config('pdf_export.chunk_size', 15)));
        $baseName = pathinfo($options['filename'] ?? ('export-'.now()->format('Ymd-His')), PATHINFO_FILENAME);
        $baseName = Str::slug($baseName) ?: 'export';

        $tmpRoot = storage_path('app/'.trim((string) config('pdf_export.temp_directory', 'pdf-export-tmp'), '/'));
        if (!is_dir($tmpRoot)) {
            mkdir($tmpRoot, 0755, true);
        }

        $runId = uniqid($baseName.'-', true);
        $chunkDir = $tmpRoot.DIRECTORY_SEPARATOR.$runId;
        mkdir($chunkDir, 0755, true);

        $chunkPaths = [];
        $chunkIndex = 0;

        try {
            foreach ($collection->chunk($chunkSize) as $chunk) {
                $chunkIndex++;
                $rendered = $renderChunk($chunk->values(), $chunkIndex);

                if (is_object($rendered) && method_exists($rendered, 'output')) {
                    if (!empty($options['paper']) || !empty($options['orientation'])) {
                        // Paper should be set by caller on the Pdf instance before return.
                    }
                    $binary = $rendered->output();
                } elseif (is_string($rendered)) {
                    $binary = $rendered;
                } else {
                    throw new \RuntimeException('Chunk renderer must return DomPDF instance or PDF binary string.');
                }

                unset($rendered);

                $chunkPath = $chunkDir.DIRECTORY_SEPARATOR.sprintf('chunk-%03d.pdf', $chunkIndex);
                if (file_put_contents($chunkPath, $binary) === false) {
                    throw new \RuntimeException("Failed writing PDF chunk {$chunkIndex}.");
                }
                unset($binary);
                $chunkPaths[] = $chunkPath;

                $this->releaseMemory();
            }

            $outputBase = $chunkDir.DIRECTORY_SEPARATOR.'merged-'.$baseName;
            $merged = $this->merger->mergeOrZip($chunkPaths, $outputBase);

            $finalName = $baseName.'.'.$merged['extension'];
            $finalPath = $tmpRoot.DIRECTORY_SEPARATOR.$runId.'-'.$finalName;
            if (!@rename($merged['path'], $finalPath) && !@copy($merged['path'], $finalPath)) {
                $finalPath = $merged['path'];
            }

            return [
                'path' => $finalPath,
                'mime' => $merged['mime'],
                'extension' => $merged['extension'],
                'merged' => $merged['merged'],
                'filename' => $finalName,
                'item_count' => $count,
                'cleanup_dir' => $chunkDir,
            ];
        } catch (\Throwable $e) {
            $this->deleteDirectory($chunkDir);
            Log::error('Chunked PDF export failed', [
                'error' => $e->getMessage(),
                'items' => $count,
            ]);
            throw $e;
        }
    }

    /**
     * Helper: render a Blade view for one chunk via DomPDF.
     *
     * @param  array<string, mixed>  $data
     */
    public function renderView(string $view, array $data, string $paper = 'a4', string $orientation = 'portrait'): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView($view, $data)->setPaper($paper, $orientation);
    }

    /**
     * Helper: render HTML string via DomPDF.
     */
    public function renderHtml(string $html, string $paper = 'a4', string $orientation = 'portrait'): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadHTML($html)->setPaper($paper, $orientation);
    }

    /**
     * @param  iterable<int, mixed>  $items
     * @param  callable(Collection $chunk, int $chunkIndex): \Barryvdh\DomPDF\PDF|string  $renderChunk
     */
    protected function respond(iterable $items, callable $renderChunk, array $options, string $disposition): BinaryFileResponse|Response
    {
        $built = $this->build($items, $renderChunk, $options);
        $filename = $options['download_name']
            ?? $built['filename'];

        // Ensure extension matches actual artifact (pdf vs zip)
        $filename = pathinfo($filename, PATHINFO_FILENAME).'.'.$built['extension'];

        $headers = [
            'Content-Type' => $built['mime'],
        ];

        if ($disposition === 'inline') {
            $headers['Content-Disposition'] = 'inline; filename="'.$filename.'"';
            $response = response()->file($built['path'], $headers)->deleteFileAfterSend(true);
        } else {
            $response = response()->download($built['path'], $filename, $headers)->deleteFileAfterSend(true);
        }

        $cleanupDir = $built['cleanup_dir'] ?? null;
        if (is_string($cleanupDir) && is_dir($cleanupDir)) {
            register_shutdown_function(static function () use ($cleanupDir) {
                if (!is_dir($cleanupDir)) {
                    return;
                }
                $items = @scandir($cleanupDir) ?: [];
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') {
                        continue;
                    }
                    @unlink($cleanupDir.DIRECTORY_SEPARATOR.$item);
                }
                @rmdir($cleanupDir);
            });
        }

        return $response;
    }

    protected function applyRuntimeLimits(): void
    {
        $limit = (string) config('pdf_export.memory_limit', '512M');
        if ($limit !== '' && $limit !== '-1') {
            @ini_set('memory_limit', $limit);
        }
        @ini_set('max_execution_time', '0');
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
    }

    protected function releaseMemory(): void
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
