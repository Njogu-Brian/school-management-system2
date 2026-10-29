<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Process;

/**
 * Merges PDF binaries produced by DomPDF.
 * Prefer qpdf/gs on the server; FPDI is a best-effort PHP fallback; ZIP is last resort.
 */
class PdfBinaryMerger
{
    /**
     * @param  list<string>  $pdfPaths  Absolute paths to PDF files (in order)
     * @return array{path: string, mime: string, extension: string, merged: bool}
     */
    public function mergeOrZip(array $pdfPaths, string $outputBasePath): array
    {
        $pdfPaths = array_values(array_filter($pdfPaths, fn ($p) => is_string($p) && is_file($p) && filesize($p) > 0));

        if ($pdfPaths === []) {
            throw new \InvalidArgumentException('No PDF chunks to merge.');
        }

        if (count($pdfPaths) === 1) {
            $dest = $outputBasePath.'.pdf';
            if (!@rename($pdfPaths[0], $dest) && !@copy($pdfPaths[0], $dest)) {
                throw new \RuntimeException('Unable to finalize single PDF chunk.');
            }

            return [
                'path' => $dest,
                'mime' => 'application/pdf',
                'extension' => 'pdf',
                'merged' => true,
            ];
        }

        $pdfOut = $outputBasePath.'.pdf';

        if ($this->mergeWithBinary($pdfPaths, $pdfOut)) {
            return [
                'path' => $pdfOut,
                'mime' => 'application/pdf',
                'extension' => 'pdf',
                'merged' => true,
            ];
        }

        if ($this->mergeWithFpdi($pdfPaths, $pdfOut)) {
            return [
                'path' => $pdfOut,
                'mime' => 'application/pdf',
                'extension' => 'pdf',
                'merged' => true,
            ];
        }

        $zipOut = $outputBasePath.'.zip';
        $this->zipFiles($pdfPaths, $zipOut);

        Log::warning('PDF binary merge unavailable; returned ZIP of chunks', [
            'chunks' => count($pdfPaths),
            'zip' => $zipOut,
        ]);

        return [
            'path' => $zipOut,
            'mime' => 'application/zip',
            'extension' => 'zip',
            'merged' => false,
        ];
    }

    /**
     * @param  list<string>  $pdfPaths
     */
    protected function mergeWithBinary(array $pdfPaths, string $outputPath): bool
    {
        foreach (config('pdf_export.merge_binaries', ['qpdf', 'gs', 'gswin64c', 'pdftk']) as $binary) {
            $resolved = $this->resolveBinary((string) $binary);
            if ($resolved === null) {
                continue;
            }

            try {
                if (str_contains(strtolower($binary), 'qpdf')) {
                    $cmd = array_merge([$resolved, '--empty', '--pages'], $pdfPaths, ['--', $outputPath]);
                } elseif (str_contains(strtolower($binary), 'pdftk')) {
                    $cmd = array_merge([$resolved], $pdfPaths, ['cat', 'output', $outputPath]);
                } else {
                    // Ghostscript
                    $cmd = array_merge([
                        $resolved,
                        '-dBATCH',
                        '-dNOPAUSE',
                        '-q',
                        '-sDEVICE=pdfwrite',
                        '-dPDFSETTINGS=/default',
                        '-sOutputFile='.$outputPath,
                    ], $pdfPaths);
                }

                $process = new Process($cmd);
                $process->setTimeout(300);
                $process->run();

                if ($process->isSuccessful() && is_file($outputPath) && filesize($outputPath) > 0) {
                    return true;
                }

                Log::info('PDF merge binary failed', [
                    'binary' => $binary,
                    'error' => $process->getErrorOutput() ?: $process->getOutput(),
                ]);
            } catch (\Throwable $e) {
                Log::info('PDF merge binary exception', [
                    'binary' => $binary,
                    'error' => $e->getMessage(),
                ]);
            }

            @unlink($outputPath);
        }

        return false;
    }

    /**
     * @param  list<string>  $pdfPaths
     */
    protected function mergeWithFpdi(array $pdfPaths, string $outputPath): bool
    {
        try {
            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);

            foreach ($pdfPaths as $path) {
                $pageCount = $pdf->setSourceFile($path);
                for ($page = 1; $page <= $pageCount; $page++) {
                    $template = $pdf->importPage($page);
                    $size = $pdf->getTemplateSize($template);
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($template);
                }
            }

            $pdf->Output('F', $outputPath);

            return is_file($outputPath) && filesize($outputPath) > 0;
        } catch (\Throwable $e) {
            Log::info('FPDI PDF merge failed (common with DomPDF 1.5+ streams)', [
                'error' => $e->getMessage(),
            ]);
            @unlink($outputPath);

            return false;
        }
    }

    /**
     * @param  list<string>  $pdfPaths
     */
    protected function zipFiles(array $pdfPaths, string $zipPath): void
    {
        $dir = dirname($zipPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create ZIP for PDF chunks.');
        }

        foreach ($pdfPaths as $i => $path) {
            $zip->addFile($path, sprintf('part-%03d.pdf', $i + 1));
        }

        $zip->close();
    }

    protected function resolveBinary(string $name): ?string
    {
        if ($name !== '' && (is_executable($name) || is_file($name))) {
            return $name;
        }

        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $process = new Process(['where', $name]);
            } else {
                $process = new Process(['bash', '-lc', 'command -v '.escapeshellarg($name)]);
            }
            $process->setTimeout(10);
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }
            $line = trim(explode("\n", str_replace("\r", '', $process->getOutput()))[0] ?? '');

            return $line !== '' ? $line : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
