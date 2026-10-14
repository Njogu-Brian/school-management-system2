<?php

namespace App\Services\Backup;

use RuntimeException;

/**
 * Ledger of binary logs whose S3 size was verified. Incomplete uploads are never written here.
 */
class BinlogLedger
{
    public static function record(string $path, string $name, int $size): void
    {
        BinlogArchiveGate::indexOf($name);
        if ($size < 4) {
            throw new RuntimeException('Refusing to record a binary log with an implausible size.');
        }

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException("Could not create ledger directory {$directory}.");
        }

        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new RuntimeException("Could not open binary log ledger {$path}.");
        }
        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException('Could not lock the binary log ledger.');
            }
            rewind($handle);
            $contents = stream_get_contents($handle);
            $existing = self::sizeIn(is_string($contents) ? $contents : '', $name);
            if ($existing === null) {
                fseek($handle, 0, SEEK_END);
                fwrite($handle, $name.' '.$size."\n");
            } elseif ($existing !== $size) {
                throw new RuntimeException("Ledger already has {$name} at {$existing} bytes, not {$size}.");
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public static function sizeFor(string $path, string $name): ?int
    {
        if (! is_file($path)) {
            return null;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            throw new RuntimeException("Could not read binary log ledger {$path}.");
        }
        $found = null;
        foreach ($lines as $line) {
            if (preg_match('/^(binlog\.\d+)\s+(\d+)$/', trim($line), $matches) !== 1) {
                continue;
            }
            if ($matches[1] === $name) {
                $found = (int) $matches[2];
            }
        }

        return $found;
    }

    private static function sizeIn(string $contents, string $name): ?int
    {
        $found = null;
        foreach (preg_split("/\r\n|\n|\r/", $contents) ?: [] as $line) {
            if (preg_match('/^(binlog\.\d+)\s+(\d+)$/', trim($line), $matches) !== 1) {
                continue;
            }
            if ($matches[1] === $name) {
                $found = (int) $matches[2];
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function names(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return [];
        }
        $names = [];
        foreach ($lines as $line) {
            if (preg_match('/^(binlog\.\d+)\s+\d+$/', trim($line), $matches) === 1) {
                $names[] = $matches[1];
            }
        }

        return $names;
    }

    public static function deletionAllowed(string $path, string $name, int $remoteSize): bool
    {
        return self::sizeFor($path, $name) === $remoteSize && BinlogArchiveGate::localCopyMayBeDeleted(true);
    }
}
