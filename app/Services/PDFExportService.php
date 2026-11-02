<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\Pdf\ChunkedDompdfExporter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class PDFExportService
{
    /**
     * Generate PDF with school branding.
     *
     * For multi-row / multi-doc exports, pass options['chunk_items'] (iterable) and the
     * view must accept a chunk via options['chunk_data_key'] (default: rows).
     * DomPDF must never receive unbounded collections in one render.
     */
    public function generatePDF($view, $data, $options = [])
    {
        try {
            // Get school branding settings
            $branding = $this->getBrandingData();
            
            // Merge branding data with view data
            $data = array_merge($data, [
                'branding' => $branding,
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => auth()->user()?->first_name ?? auth()->user()?->name ?? 'System',
            ]);

            $filename = $options['filename'] ?? 'document_' . time() . '.pdf';
            $paperSize = $options['paper_size'] ?? 'A4';
            $orientation = $options['orientation'] ?? 'portrait';

            $chunkItems = $options['chunk_items'] ?? null;
            if ($chunkItems !== null) {
                $chunkCollection = $chunkItems instanceof Collection
                    ? $chunkItems
                    : Collection::make($chunkItems);
                $chunkKey = $options['chunk_data_key'] ?? 'rows';
                if ($chunkCollection->isEmpty()) {
                    $data[$chunkKey] = [];
                } else {
                    return $this->generateChunkedPDF($view, $data, $chunkCollection, array_merge($options, [
                        'filename' => $filename,
                        'paper_size' => $paperSize,
                        'orientation' => $orientation,
                    ]));
                }
            }

            // Load PDF view
            $pdf = Pdf::loadView($view, $data);

            // Set paper size
            $pdf->setPaper($paperSize, $orientation);

            // Set PDF options
            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);

            // Apply watermark if configured
            if ($branding['watermark'] ?? false) {
                // Watermark can be applied via CSS in the view
            }
            
            // Save to storage if requested
            if ($options['save'] ?? false) {
                $path = $options['path'] ?? 'pdfs/' . $filename;
                storage_public()->put($path, $pdf->output());
                
                return [
                    'success' => true,
                    'path' => $path,
                    'url' => storage_public_url($path) ?? Storage::url($path),
                    'filename' => $filename,
                ];
            }

            // Return PDF download response (when not saving)
            if ($options['stream'] ?? false) {
                return $pdf->stream($filename);
            }

            // Return PDF download response
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('PDF generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Chunked DomPDF export for large row sets / document lists.
     * Over sync_max → queue automatically (when payload is serializable), store on S3,
     * and notify via branded email + web/mobile alert with a permanent download link.
     *
     * @param  iterable<int, mixed>  $items
     */
    public function generateChunkedPDF(string $view, array $sharedData, iterable $items, array $options = [])
    {
        $exporter = app(ChunkedDompdfExporter::class);
        $chunkKey = $options['chunk_data_key'] ?? 'rows';
        $paper = $options['paper_size'] ?? 'A4';
        $orientation = $options['orientation'] ?? 'portrait';
        $filename = $options['filename'] ?? 'document_'.time().'.pdf';
        $disposition = !empty($options['stream']) ? 'inline' : 'attachment';
        $collection = $items instanceof Collection ? $items->values() : Collection::make($items)->values();
        $count = $collection->count();
        $syncMax = (int) ($options['sync_max'] ?? config('pdf_export.sync_max_items', 150));
        $hardMax = (int) ($options['hard_max'] ?? config('pdf_export.hard_max_items', 2000));
        $hasPrepareChunk = isset($options['prepare_chunk']) && is_callable($options['prepare_chunk']);

        if ($count > $hardMax) {
            return $this->overflowResponse(
                "Too many items for PDF export ({$count}). Maximum is {$hardMax}. Narrow your filters.",
                true
            );
        }

        // Closures cannot be queued — group-key exports keep sync with allow_over_sync.
        if ($count > $syncMax && empty($options['allow_over_sync']) && ! $hasPrepareChunk) {
            return $this->queueChunkedViewExport($view, $sharedData, $collection, array_merge($options, [
                'filename' => $filename,
                'paper_size' => $paper,
                'orientation' => $orientation,
                'chunk_data_key' => $chunkKey,
            ]));
        }

        if ($count > $syncMax && $hasPrepareChunk) {
            $options['allow_over_sync'] = true;
        }

        $render = function (Collection $chunk) use ($exporter, $view, $sharedData, $chunkKey, $paper, $orientation, $options) {
            $data = $sharedData;
            $data[$chunkKey] = $chunk->values()->all();
            if (isset($options['prepare_chunk']) && is_callable($options['prepare_chunk'])) {
                $data = $options['prepare_chunk']($data, $chunk);
            }

            return $exporter->renderView($view, $data, $paper, $orientation);
        };

        $exportOptions = [
            'filename' => $filename,
            'chunk_size' => $options['chunk_size'] ?? null,
            'sync_max' => $options['sync_max'] ?? null,
            'hard_max' => $options['hard_max'] ?? null,
            'allow_over_sync' => ! empty($options['allow_over_sync']),
        ];

        if ($disposition === 'inline') {
            return $exporter->stream($collection, $render, $exportOptions);
        }

        return $exporter->download($collection, $render, $exportOptions);
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @param  array<string, mixed>  $sharedData
     * @param  array<string, mixed>  $options
     */
    protected function queueChunkedViewExport(string $view, array $sharedData, Collection $items, array $options)
    {
        $userId = (int) (auth()->id() ?: ($options['user_id'] ?? 0));
        if ($userId < 1) {
            return $this->overflowResponse('Sign in to queue a large PDF export.', true);
        }

        // Drop non-serializable / heavy branding objects that the job rebuilds.
        $serializable = $this->serializeSharedDataForQueue($sharedData);
        $title = (string) ($options['export_title'] ?? ($sharedData['title'] ?? 'PDF export'));
        $module = (string) ($options['export_module'] ?? 'general');

        \App\Jobs\GenerateQueuedViewPdfJob::dispatch(
            $userId,
            $view,
            $serializable,
            $items->values()->all(),
            [
                'filename' => $options['filename'] ?? 'export.pdf',
                'paper_size' => $options['paper_size'] ?? 'A4',
                'orientation' => $options['orientation'] ?? 'portrait',
                'chunk_size' => $options['chunk_size'] ?? 40,
                'chunk_data_key' => $options['chunk_data_key'] ?? 'rows',
                'hard_max' => $options['hard_max'] ?? null,
            ],
            $title,
            $module,
        );

        $message = "PDF export queued for {$items->count()} items. ".
            'You will get an email and an in-app / mobile alert with a permanent download link when it is ready.';

        return $this->overflowResponse($message, false);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function serializeSharedDataForQueue(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_object($value)) {
                continue;
            }
            // Rebuild branding (incl. logoBase64) on the worker — do not queue megabyte payloads.
            if ($key === 'branding' || $key === 'logoBase64' || $key === 'logo_path') {
                continue;
            }
            if (is_array($value)) {
                $out[$key] = $this->serializeSharedDataForQueue($value);
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function brandingForPdf(): array
    {
        return $this->getBrandingData();
    }

    protected function overflowResponse(string $message, bool $isError)
    {
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => ! $isError,
                'queued' => ! $isError,
                'message' => $message,
                'error' => $isError ? $message : null,
            ], $isError ? 422 : 202);
        }

        return back()->with($isError ? 'error' : 'success', $message);
    }

    /**
     * Generate PDF for download
     */
    public function downloadPDF($view, $data, $filename, $options = [])
    {
        $result = $this->generatePDF($view, $data, $options);
        
        if ($result['success']) {
            return $result['pdf']->download($filename);
        }
        
        throw new \Exception('PDF generation failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Generate PDF for stream (inline view)
     */
    public function streamPDF($view, $data, $options = [])
    {
        $result = $this->generatePDF($view, $data, $options);
        
        if ($result['success']) {
            return $result['pdf']->stream();
        }
        
        throw new \Exception('PDF generation failed: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Generate bulk PDFs (for report cards, etc.) — one DomPDF render per item, optional ZIP.
     * Prefer ChunkedDompdfExporter when the user needs a single merged PDF.
     */
    public function generateBulkPDFs($items, $view, $dataCallback, $options = [])
    {
        $pdfs = [];
        $errors = [];

        foreach ($items as $item) {
            try {
                $itemData = is_callable($dataCallback) ? $dataCallback($item) : $dataCallback;
                $result = $this->generatePDF($view, $itemData, array_merge($options, ['save' => true]));
                
                if ($result['success']) {
                    $pdfs[] = [
                        'item' => $item,
                        'path' => $result['path'],
                        'url' => $result['url'],
                    ];
                } else {
                    $errors[] = [
                        'item' => $item,
                        'error' => $result['error'] ?? 'Unknown error',
                    ];
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'item' => $item,
                    'error' => $e->getMessage(),
                ];
            }

            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        // Create ZIP file if requested
        if (($options['zip'] ?? false) && count($pdfs) > 0) {
            $zipPath = $this->createZipFile($pdfs, $options['zip_filename'] ?? 'documents.zip');
            
            return [
                'success' => true,
                'zip_path' => $zipPath,
                'zip_url' => storage_public_url($zipPath) ?? Storage::url($zipPath),
                'pdfs_count' => count($pdfs),
                'errors_count' => count($errors),
                'errors' => $errors,
            ];
        }

        return [
            'success' => true,
            'pdfs' => $pdfs,
            'errors' => $errors,
        ];
    }

    /**
     * Create ZIP file from PDF paths
     */
    protected function createZipFile($pdfs, $filename)
    {
        $zip = new \ZipArchive();
        $zipPath = storage_path('app/public/pdfs/' . $filename);
        
        // Create directory if it doesn't exist
        $zipDir = dirname($zipPath);
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            foreach ($pdfs as $pdf) {
                $filePath = storage_path('app/public/' . $pdf['path']);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, basename($pdf['path']));
                }
            }
            $zip->close();
        }

        return 'pdfs/' . $filename;
    }

    /**
     * Get school branding data
     */
    protected function getBrandingData()
    {
        $logo = setting('school_logo');
        $logoPath = null;
        $logoBase64 = null;

        $candidates = array_filter([
            $logo && file_exists(public_path('images/' . ltrim((string) $logo, '/')))
                ? public_path('images/' . ltrim((string) $logo, '/'))
                : null,
            $logo && file_exists(public_path(ltrim((string) $logo, '/')))
                ? public_path(ltrim((string) $logo, '/'))
                : null,
            $logo && is_file(storage_path('app/public/' . ltrim((string) $logo, '/')))
                ? storage_path('app/public/' . ltrim((string) $logo, '/'))
                : null,
            setting('pdf_logo_path') && is_file((string) setting('pdf_logo_path'))
                ? (string) setting('pdf_logo_path')
                : null,
        ]);

        foreach ($candidates as $path) {
            if (! is_readable($path)) {
                continue;
            }
            $logoPath = $path;
            $mime = @mime_content_type($path) ?: 'image/png';
            $logoBase64 = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
            break;
        }

        return [
            'school_name' => setting('school_name', 'School Name'),
            'school_email' => setting('school_email', ''),
            'school_phone' => setting('school_phone', ''),
            'school_address' => setting('school_address', ''),
            'school_website' => setting('school_website', ''),
            'school_logo' => $logo,
            'logo_path' => $logoPath,
            'logoBase64' => $logoBase64,
            'header_html' => setting('pdf_header_html'),
            'footer_html' => setting('pdf_footer_html'),
            'watermark' => setting('pdf_watermark'),
            'pdf_logo_path' => setting('pdf_logo_path'),
            'report_card_template' => setting('report_card_template', 'default'),
        ];
    }
}

