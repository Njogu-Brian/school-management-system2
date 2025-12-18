<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Setting;
use Symfony\Component\Process\Process;

class BackupRestoreController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin|Admin');
    }

    public function index()
    {
        $backups = $this->getBackupList();
        $schedule = Setting::getJson('backup_schedule', [
            'frequency' => 'weekly',
            'time' => '02:00',
            'last_run' => null,
        ]);
        return view('backup_restore.index', compact('backups', 'schedule'));
    }

    public function create()
    {
        try {
            $this->runBackup();
            return back()->with('success', 'Database backup created successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    public function download($filename)
    {
        $path = storage_path('app/backups/' . $filename);
        
        if (!file_exists($path)) {
            return back()->with('error', 'Backup file not found.');
        }

        return response()->download($path);
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'backup_file' => 'required|file|mimes:sql,zip',
        ]);

        $file = $request->file('backup_file');
        $path = $file->storeAs('backups', $file->getClientOriginalName());
        $fullPath = storage_path('app/' . $path);

        try {
            if ($file->getClientOriginalExtension() === 'sql') {
                $sql = file_get_contents($fullPath);
                DB::unprepared($sql);
            } else {
                return back()->with('error', 'Zip restore is not supported yet. Please upload a .sql file.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Database restored successfully. Please verify data.');
    }

    public function updateSchedule(Request $request)
    {
        $data = $request->validate([
            'frequency' => 'required|in:daily,weekly,biweekly,monthly',
            'time' => 'required|date_format:H:i',
        ]);

        $current = Setting::getJson('backup_schedule', []);
        $data['last_run'] = $current['last_run'] ?? null;

        Setting::setJson('backup_schedule', $data);

        return back()->with('success', 'Backup schedule saved.');
    }

    public static function runScheduledIfDue(): ?string
    {
        $schedule = Setting::getJson('backup_schedule', [
            'frequency' => 'weekly',
            'time' => '02:00',
            'last_run' => null,
        ]);

        $now = Carbon::now();
        $lastRun = $schedule['last_run'] ? Carbon::parse($schedule['last_run']) : null;

        // Only run when current minute hits chosen time to avoid multiple runs within the hour
        if ($now->format('H:i') !== ($schedule['time'] ?? '02:00')) {
            return null;
        }

        $due = false;
        if (!$lastRun) {
            $due = true;
        } else {
            $days = $lastRun->diffInDays($now);
            $map = [
                'daily' => 1,
                'weekly' => 7,
                'biweekly' => 14,
                'monthly' => 30,
            ];
            $threshold = $map[$schedule['frequency'] ?? 'weekly'] ?? 7;
            $due = $days >= $threshold;
        }

        if (!$due) {
            return null;
        }

        try {
            (new self)->runBackup();
            Setting::setJson('backup_schedule', [
                'frequency' => $schedule['frequency'] ?? 'weekly',
                'time' => $schedule['time'] ?? '02:00',
                'last_run' => $now->toDateTimeString(),
            ]);
            return 'Backup created at ' . $now->toDateTimeString();
        } catch (\Throwable $e) {
            return 'Scheduled backup failed: ' . $e->getMessage();
        }
    }

    protected function getBackupList()
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $files = glob($backupDir . '/*.{sql,zip}', GLOB_BRACE);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'created_at' => Carbon::createFromTimestamp(filemtime($file)),
            ];
        }

        usort($backups, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    /**
     * Run a database backup without relying on backup:run command.
     */
    protected function runBackup(): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $backupDir = env('BACKUP_STORAGE_PATH', storage_path('app/backups'));
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $timestamp = now()->format('Ymd_His');
        $filePath = "{$backupDir}/backup_{$connection}_{$timestamp}.sql";

        if ($config['driver'] === 'mysql') {
            $binary = env('MYSQLDUMP_PATH', 'mysqldump'); // Prefer PATH; override via env when needed
            if ((str_contains($binary, '\\') || str_contains($binary, '/')) && !file_exists($binary)) {
                throw new \RuntimeException("mysqldump not found at {$binary}. Set MYSQLDUMP_PATH to the full binary path or place it on PATH.");
            }
            $command = [
                $binary,
                '--user=' . $config['username'],
                '--password=' . ($config['password'] ?? ''),
                '--host=' . ($config['host'] ?? '127.0.0.1'),
                '--port=' . ($config['port'] ?? '3306'),
                $config['database'],
                '--result-file=' . $filePath,
            ];
            $env = null;
        } elseif ($config['driver'] === 'pgsql') {
            $env = ['PGPASSWORD' => $config['password'] ?? ''];
            $command = [
                'pg_dump',
                '-U', $config['username'],
                '-h', $config['host'] ?? '127.0.0.1',
                '-p', $config['port'] ?? '5432',
                '-d', $config['database'],
                '-f', $filePath,
            ];
        } elseif ($config['driver'] === 'sqlite') {
            $dbPath = $config['database'];
            if (!file_exists($dbPath)) {
                throw new \RuntimeException('SQLite database file not found.');
            }
            if (!copy($dbPath, $filePath)) {
                throw new \RuntimeException('Failed to copy SQLite database file.');
            }
            return;
        } else {
            throw new \RuntimeException("Backup not supported for driver: {$config['driver']}");
        }

        $process = new Process($command);
        if (isset($env)) {
            $process->setEnv($env + $_ENV + $_SERVER);
        }
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Backup failed: ' . $process->getErrorOutput());
        }
    }
}
