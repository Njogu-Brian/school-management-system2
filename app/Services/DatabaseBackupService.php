<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Local filesystem SQL/zip database backups (see config/backup.php).
 */
class DatabaseBackupService
{
    public function directory(): string
    {
        $dir = config('backup.storage_path', storage_path('app/backups'));
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * @return array<int, array{name: string, size: int, created_at: \Carbon\Carbon}>
     */
    public function listBackups(): array
    {
        $backupDir = $this->directory();
        $files = glob($backupDir.'/*.{sql,sql.gz,zip}', GLOB_BRACE) ?: [];
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'created_at' => Carbon::createFromTimestamp(filemtime($file)),
            ];
        }

        usort($backups, fn ($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    /**
     * Delete backup files whose last modification time is older than $days full days.
     */
    public function pruneOlderThan(int $days): int
    {
        if ($days < 1) {
            return 0;
        }

        $cutoff = Carbon::now()->subDays($days)->timestamp;
        $deleted = 0;

        foreach (glob($this->directory().'/*.{sql,sql.gz,zip}', GLOB_BRACE) ?: [] as $file) {
            if (filemtime($file) < $cutoff && @unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Keep only the latest N backups (by mtime) and delete older ones.
     */
    public function pruneKeepLatest(int $keep): int
    {
        if ($keep <= 0) {
            return 0;
        }

        $files = glob($this->directory().'/*.{sql,sql.gz,zip}', GLOB_BRACE) ?: [];
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        $deleted = 0;
        foreach (array_slice($files, $keep) as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Remove every .sql / .zip backup in the configured directory.
     */
    public function deleteAll(): int
    {
        $deleted = 0;
        foreach (glob($this->directory().'/*.{sql,sql.gz,zip}', GLOB_BRACE) ?: [] as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function absolutePathForFilename(string $filename): ?string
    {
        $name = basename($filename);
        if ($name === '' || $name !== $filename || str_contains($name, '..')) {
            return null;
        }
        if (! preg_match('/\.(sql|zip|sql\.gz)$/i', $name)) {
            return null;
        }
        $path = $this->directory().DIRECTORY_SEPARATOR.$name;

        return file_exists($path) ? $path : null;
    }

    /**
     * Upload a local backup file to S3 using config/backup.php settings.
     * Returns the object key on success.
     */
    public function uploadToS3(string $absolutePath): ?string
    {
        if (!config('backup.upload_to_s3', true)) {
            return null;
        }

        $disk = (string) config('backup.s3_disk', 's3_private');
        $prefix = (string) config('backup.s3_prefix', 'backups/mysql');
        $filename = basename($absolutePath);
        $key = trim($prefix, '/').'/'.$filename;

        $stream = @fopen($absolutePath, 'rb');
        if (!$stream) {
            throw new \RuntimeException("Could not open backup file for upload: {$absolutePath}");
        }

        try {
            $ok = Storage::disk($disk)->writeStream($key, $stream);
            if (!$ok) {
                throw new \RuntimeException("Upload to {$disk} failed for key: {$key}");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $key;
    }
}
