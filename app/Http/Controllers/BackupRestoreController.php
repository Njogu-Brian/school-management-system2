<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Carbon\Carbon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupRestoreController extends Controller
{
    public function __construct(
        protected DatabaseBackupService $backupService
    ) {
        $this->middleware('role:Super Admin|Admin');
    }

    public function index()
    {
        $backups = $this->backupService->listBackups();
        $schedule = Setting::getJson('backup_schedule', [
            'frequency' => 'weekly',
            'time' => '02:00',
            'last_run' => null,
        ]);

        return view('backup_restore.index', [
            'backups' => $backups,
            'schedule' => $schedule,
            'retentionDays' => (int) config('backup.retention_days', 5),
        ]);
    }

    public function create()
    {
        try {
            $this->runBackup();
            $pruned = $this->backupService->pruneOlderThan((int) config('backup.retention_days', 5));

            return back()->with('success', 'Database backup created successfully.'
                .($pruned > 0 ? " Removed {$pruned} backup file(s) older than retention." : ''));
        } catch (\Exception $e) {
            return back()->with('error', 'Backup failed: '.$e->getMessage());
        }
    }

    /**
     * Delete all local backup files (Super Admin only).
     */
    public function purgeAll(Request $request)
    {
        if (! $request->user()?->hasRole('Super Admin')) {
            abort(403);
        }
        $request->validate([
            'confirm_purge' => 'required|in:DELETE ALL BACKUPS',
        ]);

        $n = $this->backupService->deleteAll();

        return back()->with('success', "Deleted {$n} backup file(s).");
    }

    public function download($filename)
    {
        $path = $this->backupService->absolutePathForFilename($filename);
        if ($path) {
            return response()->download($path);
        }

        // Fallback: if local file was pruned, allow download from S3.
        $prefix = (string) config('backup.s3_prefix', 'backups/mysql');
        $disk = (string) config('backup.s3_disk', 's3_private');
        $key = trim($prefix, '/').'/'.basename($filename);
        try {
            if (Storage::disk($disk)->exists($key)) {
                return Storage::disk($disk)->download($key, basename($filename));
            }
        } catch (\Throwable $e) {
            // fall through
        }

        return back()->with('error', 'Backup file not found.');
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'backup_file' => 'required|file|mimes:sql,zip,gz',
        ]);

        $file = $request->file('backup_file');
        $path = $file->storeAs('backups', $file->getClientOriginalName());
        $fullPath = storage_path('app/'.$path);

        try {
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if ($ext === 'sql') {
                $sql = file_get_contents($fullPath);
                DB::unprepared($sql);
            } elseif ($ext === 'gz') {
                // Expect .sql.gz
                $gz = gzopen($fullPath, 'rb');
                if (!$gz) {
                    return back()->with('error', 'Could not open .gz backup for restore.');
                }
                $sql = '';
                while (!gzeof($gz)) {
                    $sql .= gzread($gz, 1024 * 1024);
                }
                gzclose($gz);
                DB::unprepared($sql);
            } else {
                return back()->with('error', 'Zip restore is not supported yet. Please upload a .sql file.');
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Restore failed: '.$e->getMessage());
        }

        return back()->with('success', 'Database restored successfully. Please verify data.');
    }

    public function updateSchedule(Request $request)
    {
        $data = $request->validate([
            'frequency' => 'required|in:hourly,every_6_hours,every_12_hours,daily,weekly,biweekly,monthly',
            'time' => 'nullable|date_format:H:i',
        ]);

        $current = Setting::getJson('backup_schedule', []);
        $data['last_run'] = $current['last_run'] ?? null;

        // For interval schedules, time is ignored.
        if (in_array($data['frequency'], ['hourly', 'every_6_hours', 'every_12_hours'], true)) {
            $data['time'] = $data['time'] ?? null;
        } else {
            $data['time'] = $data['time'] ?? '02:00';
        }

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

        $freq = $schedule['frequency'] ?? 'weekly';
        $intervalMinutesMap = [
            'hourly' => 60,
            'every_6_hours' => 360,
            'every_12_hours' => 720,
        ];

        // Interval schedules: ignore "time" and run when due based on last_run.
        if (!array_key_exists($freq, $intervalMinutesMap)) {
            if ($now->format('H:i') !== ($schedule['time'] ?? '02:00')) {
                return null;
            }
        }

        $due = false;
        if (! $lastRun) {
            $due = true;
        } else {
            if (array_key_exists($freq, $intervalMinutesMap)) {
                $due = $lastRun->diffInMinutes($now) >= $intervalMinutesMap[$freq];
            } else {
                $days = $lastRun->diffInDays($now);
                $map = [
                    'daily' => 1,
                    'weekly' => 7,
                    'biweekly' => 14,
                    'monthly' => 30,
                ];
                $threshold = $map[$freq] ?? 7;
                $due = $days >= $threshold;
            }
        }

        if (! $due) {
            return null;
        }

        try {
            $controller = app(self::class);
            $controller->runBackup();
            Setting::setJson('backup_schedule', [
                'frequency' => $freq,
                'time' => $schedule['time'] ?? '02:00',
                'last_run' => $now->toDateTimeString(),
            ]);
            $pruned = app(DatabaseBackupService::class)->pruneOlderThan((int) config('backup.retention_days', 5));
            $prunedByCount = app(DatabaseBackupService::class)->pruneKeepLatest((int) config('backup.keep_local_latest', 2));
            $msg = 'Backup created at '.$now->toDateTimeString();
            if ($pruned > 0) {
                $msg .= "; pruned {$pruned} old file(s)";
            }
            if ($prunedByCount > 0) {
                $msg .= "; pruned {$prunedByCount} by count";
            }

            return $msg;
        } catch (\Throwable $e) {
            return 'Scheduled backup failed: '.$e->getMessage();
        }
    }

    /**
     * Run a database backup without relying on backup:run command.
     */
    protected function runBackup(): void
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $backupDir = config('backup.storage_path', storage_path('app/backups'));
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $timestamp = now()->format('Ymd_His');
        $filePath = "{$backupDir}/backup_{$connection}_{$timestamp}.sql";
        $gzPath = "{$filePath}.gz";

        if ($config['driver'] === 'mysql') {
            $binary = env('MYSQLDUMP_PATH', 'mysqldump');
            if ((str_contains($binary, '\\') || str_contains($binary, '/')) && ! file_exists($binary)) {
                throw new \RuntimeException("mysqldump not found at {$binary}. Set MYSQLDUMP_PATH to the full binary path or place it on PATH.");
            }
            $command = [
                $binary,
                '--user='.$config['username'],
                '--password='.($config['password'] ?? ''),
                '--host='.($config['host'] ?? '127.0.0.1'),
                '--port='.($config['port'] ?? '3306'),
                $config['database'],
                '--result-file='.$filePath,
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
            if (! file_exists($dbPath)) {
                throw new \RuntimeException('SQLite database file not found.');
            }
            if (! copy($dbPath, $filePath)) {
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

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Backup failed: '.$process->getErrorOutput());
        }

        // Compress to .sql.gz (saves disk and is faster to upload).
        $in = @fopen($filePath, 'rb');
        if (! $in) {
            throw new \RuntimeException("Backup created but could not read file for compression: {$filePath}");
        }
        $out = @gzopen($gzPath, 'wb9');
        if (! $out) {
            fclose($in);
            throw new \RuntimeException("Could not create gzip file: {$gzPath}");
        }
        while (!feof($in)) {
            $buf = fread($in, 1024 * 1024);
            if ($buf === false) {
                break;
            }
            gzwrite($out, $buf);
        }
        fclose($in);
        gzclose($out);

        // Remove uncompressed dump.
        @unlink($filePath);

        // Upload to S3 (private) if configured.
        $this->backupService->uploadToS3($gzPath);
    }
}
