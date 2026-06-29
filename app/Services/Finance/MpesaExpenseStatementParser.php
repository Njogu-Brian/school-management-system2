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

    /**
     * Quickly count pages and verify the password / M-Pesa format without the
     * memory-heavy table extraction. Used before dispatching the async parse so
     * the password prompt stays synchronous.
     *
     * @return array{success: bool, page_count?: int, is_mpesa?: bool, error?: string, message?: string}
     */
    public function countPages(string $absolutePath, ?string $password = null): array
    {
        return $this->runScript($absolutePath, $password, ['--count-pages'], 120);
    }

    /**
     * Parse only a slice of pages (1-based, inclusive). Keeps peak memory bounded
     * so a large statement can be processed a few pages at a time.
     *
     * @return array{success: bool, transactions?: array, metadata?: array, is_mpesa?: bool, total_pages?: int, error?: string, message?: string}
     */
    public function parseRange(string $absolutePath, ?string $password, int $startPage, int $endPage, int $timeout = 300): array
    {
        return $this->runScript(
            $absolutePath,
            $password,
            ['--start-page', (string) $startPage, '--end-page', (string) $endPage],
            $timeout
        );
    }

    /**
     * Shared runner for the python parser with arbitrary extra arguments.
     *
     * @param  array<int, string>  $extraArgs
     * @return array<string, mixed>
     */
    protected function runScript(string $absolutePath, ?string $password, array $extraArgs, int $timeout): array
    {
        if (! is_file($absolutePath)) {
            return ['success' => false, 'error' => 'file_not_found', 'message' => 'Statement file could not be read.'];
        }

        $pythonCmd = PythonCommand::resolve();
        if ($pythonCmd === null) {
            return ['success' => false, 'error' => 'python_missing', 'message' => 'Python was not found. Install Python 3.9+ and run: pip install pdfplumber'];
        }

        $script = base_path('app/Services/python/mpesa_expense_statement_parser.py');
        $cmd = array_merge([$pythonCmd, $script, $absolutePath], $extraArgs);

        if ($password !== null && $password !== '') {
            $cmd[] = '--password';
            $cmd[] = $password;
        }

        $process = new Process($cmd, base_path());
        $process->setTimeout($timeout);
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

            return ['success' => false, 'error' => 'parse_failed', 'message' => 'Failed to parse statement. ' . $detail];
        }

        return $decoded;
    }
}
