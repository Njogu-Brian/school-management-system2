<?php

namespace App\Services\Backup;

use RuntimeException;

/**
 * Rejects SQL that can switch the connection onto another database.
 */
class DumpSqlGuard
{
    public static function assertRestoreSafe(string $sql): void
    {
        $offending = self::databaseStatements($sql);
        if ($offending !== []) {
            throw new RuntimeException(
                'Dump contains database-switching SQL and was not executed: '.implode('; ', $offending)
            );
        }
    }

    public static function assertReplayTargetsOnly(string $sql, string $target): void
    {
        RestoreTargetGuard::assertIdentifier($target);
        foreach (self::useTargets($sql) as $database) {
            if ($database !== $target) {
                throw new RuntimeException("Binary log replay selects {$database} instead of {$target}. It was not executed.");
            }
        }
        $offending = self::databaseStatements($sql, allowUseOf: $target);
        if ($offending !== []) {
            throw new RuntimeException(
                'Binary log replay contains database-switching SQL and was not executed: '.implode('; ', $offending)
            );
        }
    }

    public static function assertFile(string $path): void
    {
        self::assertRestoreSafe(self::readSql($path));
    }

    /**
     * @return list<string>
     */
    public static function databaseStatements(string $sql, ?string $allowUseOf = null): array
    {
        $found = [];
        foreach (preg_split("/\r\n|\n|\r/", $sql) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (preg_match_all('/(?:^|;)\s*(?:\/\*!\d+\s*)?((?:USE\s+`?[A-Za-z0-9_]+`?)|(?:CREATE\s+(?:DATABASE|SCHEMA)\b[^;]*)|(?:DROP\s+(?:DATABASE|SCHEMA)\b[^;]*))/i', $trimmed, $matches) < 1) {
                continue;
            }
            foreach ($matches[1] as $statement) {
                $statement = trim($statement);
                if ($allowUseOf !== null && preg_match('/^USE\s+`?([A-Za-z0-9_]+)`?$/i', $statement, $use) === 1 && $use[1] === $allowUseOf) {
                    continue;
                }
                $found[] = $statement;
            }
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function useTargets(string $sql): array
    {
        $targets = [];
        foreach (preg_split("/\r\n|\n|\r/", $sql) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (preg_match_all('/(?:^|;)\s*(?:\/\*!\d+\s*)?USE\s+`?([A-Za-z0-9_]+)`?/i', $trimmed, $matches) < 1) {
                continue;
            }
            foreach ($matches[1] as $database) {
                $targets[] = $database;
            }
        }

        return $targets;
    }

    public static function readSql(string $path): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("SQL file not found: {$path}");
        }

        if (str_ends_with(strtolower($path), '.gz')) {
            $handle = gzopen($path, 'rb');
            if ($handle === false) {
                throw new RuntimeException("gzip could not open {$path}");
            }
            $sql = '';
            while (! gzeof($handle)) {
                $chunk = gzread($handle, 1024 * 1024);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                $sql .= $chunk;
            }
            gzclose($handle);

            return $sql;
        }

        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException("Could not read {$path}");
        }

        return $sql;
    }
}
