<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Verifies a dump or closed binary log, then uploads it and a manifest.
 * This publisher never deletes remote objects.
 */
class BackupPublisher
{
    public const TIERS = ['ondemand', 'frequent', 'daily', 'weekly', 'monthly', 'binlog'];

    /** Tiers copied to the optional protected recovery disk. */
    private const RECOVERY_TIERS = ['daily', 'weekly', 'monthly', 'binlog'];

    public function __construct(
        private readonly BackupDrStatus $status = new BackupDrStatus,
    ) {}

    /**
     * @param  array{database?: string, mysql_version?: string, binlog_name?: string}  $context
     * @return array<string, mixed>
     */
    public function publish(string $absolutePath, string $tier, array $context = []): array
    {
        if (! in_array($tier, self::TIERS, true)) {
            throw new RuntimeException("Unknown backup tier: {$tier}");
        }

        if (! is_file($absolutePath)) {
            throw new RuntimeException("Backup file not found: {$absolutePath}");
        }

        $isBinlog = $tier === 'binlog';
        $coordinates = null;
        $filename = basename($absolutePath);
        if ($isBinlog) {
            $objectName = $context['binlog_name'] ?? null;
            if (! is_string($objectName) || preg_match('/^binlog\.\d+$/', $objectName) !== 1) {
                throw new RuntimeException('Refusing to archive a binary log without its binlog.NNNNNN name.');
            }
            $expectedSize = $context['expected_size'] ?? null;
            if (! is_numeric($expectedSize)) {
                throw new RuntimeException('Refusing to archive a binary log without the size from SHOW BINARY LOGS.');
            }
            BinlogArchiveGate::assertComplete($absolutePath, (int) $expectedSize);
            $filename = $objectName;
        } else {
            $header = MysqlBackupInspector::readGzipHeader($absolutePath);
            $coordinates = MysqlBackupInspector::parseBinlogCoordinates($header);
        }

        $size = filesize($absolutePath);
        if ($size === false || $size < 1) {
            throw new RuntimeException("Backup file is empty: {$absolutePath}");
        }

        $sha256 = hash_file('sha256', $absolutePath);
        if ($sha256 === false) {
            throw new RuntimeException("Could not checksum backup: {$absolutePath}");
        }

        $completedAt = now()->toIso8601String();
        $disk = (string) config('backup.s3_disk', 's3_private');
        $key = null;
        $recoveryKey = null;
        $recoveryDisk = $this->recoveryDisk();

        if (config('backup.upload_to_s3', true)) {
            $key = $this->prefixFor($tier).'/'.$filename;
            $this->uploadFile($disk, $key, $absolutePath, $size);
        }

        if ($isBinlog && $key === null) {
            throw new RuntimeException('Binary log was not uploaded to S3. It was not recorded as archived.');
        }

        $manifest = [
            'tier' => $tier,
            'filename' => $filename,
            'completed_at' => $completedAt,
            'database' => $context['database'] ?? $this->configuredDatabase(),
            'mysql_version' => $context['mysql_version'] ?? $this->detectMysqlVersion(),
            'binlog_file' => $coordinates['file'] ?? null,
            'binlog_position' => $coordinates['position'] ?? null,
            's3_disk' => $key ? $disk : null,
            's3_key' => $key,
            'sha256' => $sha256,
            'size_bytes' => $size,
            'gzip_verified' => ! $isBinlog,
            'recovery_disk' => null,
            'recovery_key' => null,
        ];

        if ($key && $recoveryDisk && in_array($tier, self::RECOVERY_TIERS, true)) {
            $recoveryKey = trim((string) config('backup.recovery_prefix', 'recovery/mysql'), '/').'/'.$tier.'/'.$filename;
            $this->uploadFile($recoveryDisk, $recoveryKey, $absolutePath, $size);
            $manifest['recovery_disk'] = $recoveryDisk;
            $manifest['recovery_key'] = $recoveryKey;
        }

        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Could not encode the backup manifest.');
        }

        file_put_contents($absolutePath.'.manifest.json', $encoded.PHP_EOL);
        file_put_contents($absolutePath.'.sha256', $sha256.'  '.$filename.PHP_EOL);

        if ($key) {
            $this->uploadString($disk, $key.'.manifest.json', $encoded);
            $this->uploadString($disk, $key.'.sha256', $sha256.'  '.$filename.PHP_EOL);
            if ($recoveryKey && $recoveryDisk) {
                $this->uploadString($recoveryDisk, $recoveryKey.'.manifest.json', $encoded);
                $this->uploadString($recoveryDisk, $recoveryKey.'.sha256', $sha256.'  '.$filename.PHP_EOL);
            }
        }

        if ($isBinlog) {
            $this->status->merge([
                'binlog' => [
                    'disabled' => false,
                    'latest_archived' => $context['binlog_name'] ?? $filename,
                    'archived_at' => $completedAt,
                    's3_key' => $key,
                    'sha256' => $sha256,
                    'size_bytes' => $size,
                ],
            ]);
        } else {
            $this->status->merge([
                $tier => [
                    'completed_at' => $completedAt,
                    'filename' => $filename,
                    's3_key' => $key,
                    'sha256' => $sha256,
                    'size_bytes' => $size,
                    'binlog_file' => $manifest['binlog_file'],
                    'binlog_position' => $manifest['binlog_position'],
                    'recovery_key' => $recoveryKey,
                ],
            ]);
        }

        return $manifest;
    }

    public function markBinlogDisabled(string $reason): void
    {
        $this->status->merge([
            'binlog' => [
                'disabled' => true,
                'reason' => $reason,
                'recorded_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function prefixFor(string $tier): string
    {
        $base = trim((string) config('backup.s3_prefix', 'backups/mysql'), '/');

        return match ($tier) {
            'ondemand' => $base,
            'binlog' => $base.'/binlogs',
            default => $base.'/'.$tier,
        };
    }

    private function uploadFile(string $disk, string $key, string $absolutePath, int $size): void
    {
        $stream = fopen($absolutePath, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Could not open backup file for upload: {$absolutePath}");
        }

        try {
            $ok = Storage::disk($disk)->writeStream($key, $stream);
            if ($ok === false) {
                throw new RuntimeException("Upload to {$disk} failed for key: {$key}");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $remoteSize = Storage::disk($disk)->size($key);
        if ($remoteSize !== $size) {
            throw new RuntimeException("Uploaded object size for {$key} was {$remoteSize}, expected {$size}. The file was not treated as a verified backup.");
        }
    }

    private function uploadString(string $disk, string $key, string $contents): void
    {
        $ok = Storage::disk($disk)->put($key, $contents);
        if ($ok === false) {
            throw new RuntimeException("Upload to {$disk} failed for key: {$key}");
        }
    }

    private function recoveryDisk(): ?string
    {
        $disk = config('backup.recovery_disk');
        if (! is_string($disk) || trim($disk) === '') {
            return null;
        }

        return trim($disk);
    }

    private function configuredDatabase(): ?string
    {
        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}.database");

        return is_string($database) ? $database : null;
    }

    private function detectMysqlVersion(): ?string
    {
        try {
            $row = DB::selectOne('SELECT VERSION() AS version');
            $version = is_object($row) ? ($row->version ?? null) : null;

            return is_string($version) ? $version : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
