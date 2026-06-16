<?php

namespace App\Services\Finance;

use App\Support\PythonCommand;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class MpesaExpenseStatementParser
{
    public function parse(string $absolutePath, ?string $password = null): array
    {
        if (! is_file($absolutePath)) {
            return [
                'success' => false,
                'error' => 'file_not_found',
                'message' => 'Statement file could not be read after upload.',
            ];
        }

        $pythonCmd = PythonCommand::resolve();
        if ($pythonCmd === null) {
            return [
                'success' => false,
                'error' => 'python_missing',
                'message' => 'Python was not found. Install Python 3.9+ and run: pip install pdfplumber',
            ];
        }

        @set_time_limit(900);

        $script = base_path('app/Services/python/mpesa_expense_statement_parser.py');
        $cmd = [$pythonCmd, $script, $absolutePath];

        if ($password !== null && $password !== '') {
            $cmd[] = '--password';
            $cmd[] = $password;
        }

        $process = new Process($cmd, base_path());
        $process->setTimeout(900);
        $process->run();

        $output = trim($process->getOutput());
        $stderr = trim($process->getErrorOutput());
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            Log::error('M-Pesa expense parser returned invalid JSON', [
                'cmd' => $cmd,
                'exit' => $process->getExitCode(),
                'stderr' => $stderr,
                'stdout_preview' => substr($output, 0, 500),
            ]);

            $detail = $stderr !== '' ? $stderr : ($output !== '' ? substr($output, 0, 200) : 'No output from parser.');
            if (str_contains(strtolower($detail), 'pdfplumber')) {
                $detail = 'Install pdfplumber: pip install pdfplumber';
            }

            return [
                'success' => false,
                'error' => 'parse_failed',
                'message' => 'Failed to parse statement. '.$detail,
            ];
        }

        if (! ($decoded['success'] ?? false)) {
            return [
                'success' => false,
                'error' => $decoded['error'] ?? 'parse_failed',
                'message' => $decoded['message'] ?? 'Could not parse M-Pesa statement.',
            ];
        }

        return $decoded;
    }

    public function isPasswordProtected(string $absolutePath): bool
    {
        $result = $this->parse($absolutePath);

        return ($result['error'] ?? null) === 'password_required';
    }
}
