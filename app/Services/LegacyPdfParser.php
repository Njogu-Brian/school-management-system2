<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

/**
 * Legacy PDF parser that shells out to Python (parse.py) for extraction.
 */
class LegacyPdfParser
{
    /**
     * Parse PDF to array of text lines using Python parse.py.
     *
     * @return array lines of text
     */
    public function getLines(string $pdfPath): array
    {
        $script = base_path('app/Services/python/parse.py');
        $cmd = ['python', $script, $pdfPath];

        $process = new Process($cmd, base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('Python parser failed', [
                'cmd' => $cmd,
                'exit' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
                'stdout' => $process->getOutput(),
            ]);
            return [];
        }

        $output = $process->getOutput();
        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            Log::error('Python parser returned invalid JSON', [
                'cmd' => $cmd,
                'stdout' => $output,
                'stderr' => $process->getErrorOutput(),
            ]);
            return [];
        }

        return array_values(array_filter(array_map('trim', $decoded), fn($l) => $l !== ''));
    }
}

