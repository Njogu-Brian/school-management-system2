<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class PDFExportService
{
    /**
     * Generate PDF with school branding
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

            // Load PDF view
            $pdf = Pdf::loadView($view, $data);

            // Set paper size
            $paperSize = $options['paper_size'] ?? 'A4';
            $pdf->setPaper($paperSize, $options['orientation'] ?? 'portrait');

            // Set PDF options
            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);

            // Apply watermark if configured
            if ($branding['watermark'] ?? false) {
                // Watermark can be applied via CSS in the view
            }

            // Return PDF for download (default behavior)
            $filename = $options['filename'] ?? 'document_' . time() . '.pdf';
            
            // Save to storage if requested
            if ($options['save'] ?? false) {
                $path = $options['path'] ?? 'pdfs/' . $filename;
                storage_public()->put($path, $pdf->output());
                
                return [
                    'success' => true,
                    'path' => $path,
                    'url' => Storage::url($path),
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
     * Generate bulk PDFs (for report cards, etc.)
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
        }

        // Create ZIP file if requested
        if (($options['zip'] ?? false) && count($pdfs) > 0) {
            $zipPath = $this->createZipFile($pdfs, $options['zip_filename'] ?? 'documents.zip');
            
            return [
                'success' => true,
                'zip_path' => $zipPath,
                'zip_url' => Storage::url($zipPath),
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
        return [
            'school_name' => setting('school_name', 'School Name'),
            'school_email' => setting('school_email', ''),
            'school_phone' => setting('school_phone', ''),
            'school_address' => setting('school_address', ''),
            'school_logo' => setting('school_logo'),
            'logo_path' => (function () {
                $logo = setting('school_logo');
                if ($logo && \Illuminate\Support\Facades\storage_public()->exists($logo)) {
                    return storage_path('app/public/' . $logo);
                }
                if ($logo && file_exists(public_path('images/' . $logo))) {
                    return public_path('images/' . $logo);
                }
                return null;
            })(),
            'header_html' => setting('pdf_header_html'),
            'footer_html' => setting('pdf_footer_html'),
            'watermark' => setting('pdf_watermark'),
            'pdf_logo_path' => setting('pdf_logo_path'),
            'report_card_template' => setting('report_card_template', 'default'),
        ];
    }
}

