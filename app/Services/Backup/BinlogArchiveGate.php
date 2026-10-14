<?php

namespace App\Services\Backup;

use RuntimeException;

/**
 * Decides whether a binary log is complete, continuous, and safe to record or delete.
 * A five-minute cron is not a recovery point. The recoverable point is the last
 * closed log whose byte size was verified in S3. The active log is never part of that point.
 */
class BinlogArchiveGate
{
    public static function assertComplete(string $path, int $expectedSize): void
    {
        if ($expectedSize < 4) {
            throw new RuntimeException('MySQL reported a binary log size that is too small to be a complete log.');
        }

        $actual = filesize($path);
        if ($actual === false || $actual !== $expectedSize) {
            $shown = $actual === false ? 'unreadable' : (string) $actual;
            throw new RuntimeException("Binary log is incomplete: file is {$shown} bytes, MySQL reports {$expectedSize}. It was not recorded as archived.");
        }

        MysqlBackupInspector::assertBinaryLog($path);
    }

    public static function assertReadyToRecord(int $expectedSize, int $localSize, bool $uploadSucceeded, ?int $remoteSize): void
    {
        if ($localSize !== $expectedSize) {
            throw new RuntimeException('Local binary log size does not match MySQL. It was not recorded as archived.');
        }
        if (! $uploadSucceeded || $remoteSize !== $expectedSize) {
            throw new RuntimeException('S3 upload was not verified. The log was not recorded as archived and the local copy was kept.');
        }
    }

    public static function localCopyMayBeDeleted(bool $s3SizeMatchesLedger): bool
    {
        return $s3SizeMatchesLedger;
    }

    public static function indexOf(string $name): int
    {
        if (preg_match('/^binlog\.(\d+)$/', $name, $matches) !== 1) {
            throw new RuntimeException("Unexpected binary log name {$name}. This server uses binlog.NNNNNN.");
        }

        return (int) $matches[1];
    }

    public static function nameFor(int $index): string
    {
        return sprintf('binlog.%06d', $index);
    }

    /**
     * Logs that are neither still on the server nor already verified in S3.
     *
     * @param  list<string>  $closedOnServer
     * @param  list<string>  $verifiedInS3
     * @return list<string>
     */
    public static function missingSequence(array $closedOnServer, array $verifiedInS3, string $coverageStart): array
    {
        $start = self::indexOf($coverageStart);
        $end = $start;
        $have = [];
        foreach ([...$closedOnServer, ...$verifiedInS3] as $name) {
            $index = self::indexOf($name);
            $have[$index] = true;
            if ($index > $end) {
                $end = $index;
            }
        }

        $missing = [];
        for ($index = $start; $index <= $end; $index++) {
            if (! isset($have[$index])) {
                $missing[] = self::nameFor($index);
            }
        }

        return $missing;
    }
}
