<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SystemLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin|Admin');
    }

    public function index(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');
        
        // Check if log file exists
        if (!File::exists($logFile)) {
            return view('system-logs.index', [
                'logs' => collect(),
                'levels' => $this->getLogLevels(),
                'error' => 'Log file not found. No errors have been logged yet.'
            ]);
        }

        // Get log level filter
        $levelFilter = $request->input('level', 'all');
        $search = $request->input('search', '');
        $dateFilter = $request->input('date', '');

        // Read log file
        $logs = $this->parseLogFile($logFile, $levelFilter, $search, $dateFilter);

        // Paginate manually
        $perPage = 50;
        $currentPage = $request->input('page', 1);
        $total = $logs->count();
        $paginatedLogs = $logs->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return view('system-logs.index', [
            'logs' => $paginatedLogs,
            'levels' => $this->getLogLevels(),
            'currentLevel' => $levelFilter,
            'currentSearch' => $search,
            'currentDate' => $dateFilter,
            'currentPage' => $currentPage,
            'totalPages' => ceil($total / $perPage),
            'total' => $total,
        ]);
    }

    protected function parseLogFile($filePath, $levelFilter = 'all', $search = '', $dateFilter = '')
    {
        try {
            $content = File::get($filePath);
        } catch (\Exception $e) {
            return collect();
        }

        $lines = explode("\n", $content);
        
        $logs = collect();
        $currentLog = null;
        $buffer = [];

        foreach ($lines as $line) {
            // Laravel log format: [2024-01-01 12:00:00] local.ERROR: message
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (.+?)\.(.+?): (.+)$/', $line, $matches)) {
                // Save previous log if exists
                if ($currentLog) {
                    $currentLog['stack'] = trim(implode("\n", $buffer));
                    if ($this->shouldIncludeLog($currentLog, $levelFilter, $search, $dateFilter)) {
                        $logs->push($currentLog);
                    }
                }

                // Start new log entry
                $currentLog = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2],
                    'level' => strtoupper(trim($matches[3])),
                    'message' => trim($matches[4]),
                    'stack' => '',
                ];
                $buffer = [];
            } elseif ($currentLog) {
                // Continue building stack trace (skip empty lines at start of buffer)
                if (!empty(trim($line)) || !empty($buffer)) {
                    $buffer[] = $line;
                }
            }
        }

        // Don't forget the last log
        if ($currentLog) {
            $currentLog['stack'] = trim(implode("\n", $buffer));
            if ($this->shouldIncludeLog($currentLog, $levelFilter, $search, $dateFilter)) {
                $logs->push($currentLog);
            }
        }

        // Reverse to show newest first
        return $logs->reverse()->values();
    }

    protected function shouldIncludeLog($log, $levelFilter, $search, $dateFilter)
    {
        // Filter by level
        if ($levelFilter !== 'all' && strtolower($log['level']) !== strtolower($levelFilter)) {
            return false;
        }

        // Filter by date
        if ($dateFilter && !str_starts_with($log['timestamp'], $dateFilter)) {
            return false;
        }

        // Filter by search
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

    public function clear()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return redirect()->route('system-logs.index')
            ->with('success', 'Log file cleared successfully.');
    }

    public function download()
    {
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            return back()->with('error', 'Log file not found.');
        }

        return response()->download($logFile, 'laravel-' . date('Y-m-d') . '.log');
    }
}
