<?php

namespace App\Services\Backup;

use RuntimeException;

/**
 * Local recovery status written by the dump and binary-log archive jobs.
 * This file is the input to backup:health. It is not itself a backup.
 */
class BackupDrStatus
{
    public function __construct(private ?string $path = null)
    {
        $this->path = $path ?? storage_path('app/backups/dr-status.json');
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function read(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($this->path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $patch
     * @return array<string, mixed>
     */
    public function merge(array $patch): array
    {
        $current = $this->read();
        foreach ($patch as $key => $value) {
            $current[$key] = $value;
        }

        $dir = dirname($this->path);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("Could not create backup status directory: {$dir}");
        }

        $encoded = json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new RuntimeException('Could not encode backup status.');
        }

        $temporary = $this->path.'.tmp';
        if (file_put_contents($temporary, $encoded.PHP_EOL) === false) {
            throw new RuntimeException("Could not write backup status: {$temporary}");
        }

        if (! rename($temporary, $this->path)) {
            @unlink($temporary);
            throw new RuntimeException("Could not replace backup status: {$this->path}");
        }

        return $current;
    }
}
