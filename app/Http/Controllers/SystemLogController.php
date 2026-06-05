<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class SystemLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin|Admin');
    }

    public function index(Request $request)
    {
        $levelFilter = (string) ($request->input('level') ?: 'all');
        $categoryFilter = (string) ($request->input('category') ?: 'all');
        $search = (string) ($request->input('search') ?? '');
        $dateFilter = (string) ($request->input('date') ?? '');

        $logFiles = $this->resolveLogFilePaths($dateFilter);

        if ($logFiles === []) {
            return view('system-logs.index', [
                'logs' => collect(),
                'levels' => $this->getLogLevels(),
                'categories' => $this->getLogCategories(),
                'error' => 'No log files found. With LOG_CHANNEL=daily, logs are stored as storage/logs/laravel-YYYY-MM-DD.log.',
                'logFiles' => [],
                'configuredLogLevel' => config('logging.channels.daily.level', config('logging.level', 'debug')),
            ]);
        }

        $logs = $this->collectLogsFromFiles($logFiles, $levelFilter, $search, $dateFilter, $categoryFilter);

        $perPage = 50;
        $currentPage = (int) $request->input('page', 1);
        $total = $logs->count();
        $paginatedLogs = $logs->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return view('system-logs.index', [
            'logs' => $paginatedLogs,
            'levels' => $this->getLogLevels(),
            'categories' => $this->getLogCategories(),
            'currentLevel' => $levelFilter,
            'currentCategory' => $categoryFilter,
            'currentSearch' => $search,
            'currentDate' => $dateFilter,
            'currentPage' => $currentPage,
            'totalPages' => (int) ceil($total / $perPage),
            'total' => $total,
            'logFiles' => array_map('basename', $logFiles),
            'configuredLogLevel' => config('logging.channels.daily.level', config('logging.level', 'debug')),
        ]);
    }

    /**
     * Resolve Laravel log files (single + daily rotation).
     *
     * @return list<string> Absolute paths, newest first.
     */
    protected function resolveLogFilePaths(?string $dateFilter = ''): array
    {
        $dateFilter = $dateFilter ?? '';
        $logsDir = storage_path('logs');

        if ($dateFilter !== '') {
            $dated = $logsDir . '/laravel-' . $dateFilter . '.log';
            if (File::exists($dated) && filesize($dated) > 0) {
                return [$dated];
            }

            return [];
        }

        $paths = [];

        $dailyFiles = glob($logsDir . '/laravel-*.log') ?: [];
        rsort($dailyFiles, SORT_STRING);

        $retentionDays = (int) config('logging.channels.daily.days', 14);
        if ($retentionDays < 1) {
            $retentionDays = 14;
        }

        foreach (array_slice($dailyFiles, 0, $retentionDays) as $file) {
            if (filesize($file) > 0) {
                $paths[] = $file;
            }
        }

        $single = $logsDir . '/laravel.log';
        if (File::exists($single) && filesize($single) > 0 && ! in_array($single, $paths, true)) {
            $paths[] = $single;
        }

        return $paths;
    }

    protected function collectLogsFromFiles(
        array $filePaths,
        string $levelFilter = 'all',
        string $search = '',
        string $dateFilter = '',
        string $categoryFilter = 'all'
    ): Collection {
        $logs = collect();

        foreach ($filePaths as $path) {
            $logs = $logs->merge($this->parseLogFile($path, $levelFilter, $search, $dateFilter, $categoryFilter));
        }

        return $logs
            ->sortByDesc('timestamp')
            ->values();
    }

    protected function parseLogFile(
        $filePath,
        $levelFilter = 'all',
        $search = '',
        $dateFilter = '',
        $categoryFilter = 'all'
    ) {
        try {
            $fileSize = filesize($filePath);
            if ($fileSize === false || $fileSize === 0) {
                return collect();
            }

            $maxBytes = 300 * 1024;

            if ($fileSize > $maxBytes) {
                $handle = fopen($filePath, 'r');
                fseek($handle, -$maxBytes, SEEK_END);
                $content = fread($handle, $maxBytes);
                fclose($handle);

                $firstNewline = strpos($content, "\n");
                if ($firstNewline !== false) {
                    $content = substr($content, $firstNewline + 1);
                }
            } else {
                $content = File::get($filePath);
            }
        } catch (\Exception $e) {
            return collect();
        }

        $lines = explode("\n", $content);

        $logs = collect();
        $currentLog = null;
        $buffer = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (.+?)\.(.+?): (.+)$/', $line, $matches)) {
                if ($currentLog) {
                    $currentLog['stack'] = trim(implode("\n", $buffer));
                    if ($this->shouldIncludeLog($currentLog, $levelFilter, $search, $dateFilter, $categoryFilter)) {
                        $logs->push($currentLog);
                    }
                }

                $message = trim($matches[4]);
                $currentLog = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2],
                    'level' => strtoupper(trim($matches[3])),
                    'message' => $message,
                    'category' => $this->categorizeLogMessage($message),
                    'stack' => '',
                ];
                $buffer = [];
            } elseif ($currentLog) {
                if (! empty(trim($line)) || ! empty($buffer)) {
                    $buffer[] = $line;
                }
            }
        }

        if ($currentLog) {
            $currentLog['stack'] = trim(implode("\n", $buffer));
            if ($this->shouldIncludeLog($currentLog, $levelFilter, $search, $dateFilter, $categoryFilter)) {
                $logs->push($currentLog);
            }
        }

        return $logs->reverse()->values();
    }

    protected function shouldIncludeLog($log, $levelFilter, $search, $dateFilter, $categoryFilter = 'all')
    {
        if ($levelFilter !== 'all' && strtolower($log['level']) !== strtolower($levelFilter)) {
            return false;
        }

        if ($categoryFilter !== 'all' && ($log['category'] ?? 'system') !== $categoryFilter) {
            return false;
        }

        if ($dateFilter && ! str_starts_with($log['timestamp'], $dateFilter)) {
            return false;
        }

        if ($search) {
            $searchLower = strtolower($search);
            $messageLower = strtolower($log['message']);
            $stackLower = strtolower($log['stack']);

            if (strpos($messageLower, $searchLower) === false &&
                strpos($stackLower, $searchLower) === false) {
                return false;
            }
        }

        return true;
    }

    protected function getLogLevels()
    {
        return [
            'all' => 'All Levels',
            'error' => 'Error',
            'critical' => 'Critical',
            'alert' => 'Alert',
            'emergency' => 'Emergency',
            'warning' => 'Warning',
            'info' => 'Info',
            'debug' => 'Debug',
        ];
    }

    protected function getLogCategories(): array
    {
        return [
            'all' => 'All categories',
            'sms_credits' => 'SMS — insufficient credits',
            'sms' => 'SMS — other',
            'whatsapp' => 'WhatsApp',
            'communications' => 'Communications pause/resume',
            'queue' => 'Queue & scheduler',
            'payment' => 'Payments & M-Pesa',
            'database' => 'Database',
            'system' => 'System / other',
        ];
    }

    protected function categorizeLogMessage(string $message): string
    {
        $m = strtolower($message);

        if (str_contains($m, 'insufficient credits')
            || str_contains($m, 'insufficient sms')
            || str_contains($m, 'sms sending blocked')) {
            return 'sms_credits';
        }

        if (str_contains($m, 'communications paused')
            || str_contains($m, 'communications resumed')
            || str_contains($m, 'communications are paused')) {
            return 'communications';
        }

        if (str_contains($m, 'whatsapp')) {
            return 'whatsapp';
        }

        if (str_contains($m, 'sms')
            || str_contains($m, 'bulk sms')
            || str_contains($m, 'dlr')) {
            return 'sms';
        }

        if (str_contains($m, 'scheduled command')
            || str_contains($m, 'queue:work')
            || str_contains($m, 'bulk send')
            || str_contains($m, 'job deferred')
            || str_contains($m, 'job halted')) {
            return 'queue';
        }

        if (str_contains($m, 'mpesa')
            || str_contains($m, 'm-pesa')
            || str_contains($m, 'payment')
            || str_contains($m, 'stk')) {
            return 'payment';
        }

        if (str_contains($m, 'sqlstate')
            || str_contains($m, 'mysql')
            || str_contains($m, 'database')
            || str_contains($m, 'connection refused')) {
            return 'database';
        }

        return 'system';
    }

    public function clear()
    {
        $cleared = 0;

        foreach ($this->allManagedLogPaths() as $path) {
            if (File::exists($path)) {
                File::put($path, '');
                $cleared++;
            }
        }

        return redirect()->route('system-logs.index')
            ->with('success', $cleared > 0
                ? "Cleared {$cleared} log file(s)."
                : 'No log files to clear.');
    }

    public function download()
    {
        $logFiles = $this->resolveLogFilePaths();

        if ($logFiles === []) {
            return back()->with('error', 'Log file not found.');
        }

        $path = $logFiles[0];

        return response()->download($path, basename($path));
    }

    /**
     * All Laravel application log files (single + daily), for clear/download.
     *
     * @return list<string>
     */
    protected function allManagedLogPaths(): array
    {
        $logsDir = storage_path('logs');
        $paths = glob($logsDir . '/laravel*.log') ?: [];

        $single = $logsDir . '/laravel.log';
        if (File::exists($single) && ! in_array($single, $paths, true)) {
            $paths[] = $single;
        }

        return array_values(array_unique($paths));
    }
}
