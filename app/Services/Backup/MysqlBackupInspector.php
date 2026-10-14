<?php

namespace App\Services\Backup;

use InvalidArgumentException;
use RuntimeException;

/**
 * Pure checks for mysqldump output, binary logs, and client option files.
 */
class MysqlBackupInspector
{
    public const BINLOG_MAGIC = "\xfe\x62\x69\x6e";

    public static function coordinateFlag(string $mysqldumpHelp, bool $logBinEnabled): ?string
    {
        if (! $logBinEnabled) {
            return null;
        }

        if (str_contains($mysqldumpHelp, '--source-data')) {
            return '--source-data=2';
        }

        if (str_contains($mysqldumpHelp, '--master-data')) {
            return '--master-data=2';
        }

        return null;
    }

    public static function isCoordinatePrivilegeError(string $error): bool
    {
        $error = strtolower($error);

        foreach (['replication client', 'replication slave', 'binlog', 'reload', 'super privilege'] as $needle) {
            if (str_contains($error, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{file: string, position: int}|null
     */
    public static function parseBinlogCoordinates(string $sqlHeader): ?array
    {
        $patterns = [
            "/SOURCE_LOG_FILE='([^']+)',\\s*SOURCE_LOG_POS=(\\d+)/",
            "/MASTER_LOG_FILE='([^']+)',\\s*MASTER_LOG_POS=(\\d+)/",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sqlHeader, $matches) === 1) {
                return [
                    'file' => $matches[1],
                    'position' => (int) $matches[2],
                ];
            }
        }

        return null;
    }

    public static function assertGzipSql(string $path): void
    {
        if (! is_file($path)) {
            throw new RuntimeException("Backup file not found: {$path}");
        }

        $handle = @gzopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("gzip could not open backup: {$path}");
        }

        $chunk = gzread($handle, 4096);
        gzclose($handle);

        if (! is_string($chunk) || $chunk === '') {
            throw new RuntimeException("gzip backup is empty or unreadable: {$path}");
        }

        $trimmed = ltrim($chunk);
        $looksLikeSql = str_contains($chunk, 'MySQL dump')
            || str_contains($chunk, 'MariaDB dump')
            || str_starts_with($trimmed, '--')
            || str_starts_with($trimmed, '/*');

        if (! $looksLikeSql) {
            throw new RuntimeException('gzip backup does not look like a SQL dump.');
        }
    }

    public static function readGzipHeader(string $path, int $maxBytes = 65536): string
    {
        self::assertGzipSql($path);

        $handle = gzopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("gzip could not open backup: {$path}");
        }

        $header = '';
        while (strlen($header) < $maxBytes && ! gzeof($handle)) {
            $chunk = gzread($handle, min(8192, $maxBytes - strlen($header)));
            if ($chunk === false || $chunk === '') {
                break;
            }
            $header .= $chunk;
        }
        gzclose($handle);

        return $header;
    }

    public static function assertBinaryLog(string $path): void
    {
        if (! is_file($path)) {
            throw new RuntimeException("Binary log not found: {$path}");
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Could not read binary log: {$path}");
        }

        $magic = fread($handle, 4);
        fclose($handle);

        if ($magic !== self::BINLOG_MAGIC) {
            throw new RuntimeException('File is not a MySQL binary log. Refusing to archive a partial or non-log file.');
        }
    }

    public static function writeClientDefaultsFile(string $user, string $password, string $host, string $port): string
    {
        if (str_contains($user, "\n") || str_contains($user, "\r")
            || str_contains($password, "\n") || str_contains($password, "\r")
            || str_contains($host, "\n") || str_contains($host, "\r")) {
            throw new InvalidArgumentException('Database credentials must not contain newlines.');
        }

        $body = "[client]\n"
            .'user="'.self::escapeOptionValue($user)."\"\n"
            .'password="'.self::escapeOptionValue($password)."\"\n"
            .'host="'.self::escapeOptionValue($host)."\"\n"
            .'port="'.self::escapeOptionValue($port)."\"\n";

        $path = tempnam(sys_get_temp_dir(), 'erp-mysql-');
        if ($path === false) {
            throw new RuntimeException('Could not create a MySQL defaults file.');
        }

        if (file_put_contents($path, $body) === false) {
            throw new RuntimeException('Could not write a MySQL defaults file.');
        }

        @chmod($path, 0600);

        return str_replace('\\', '/', $path);
    }

    /**
     * MySQL option files treat an unescaped # as a comment, including inside quotes.
     * Escape every character that would truncate or reinterpret the value.
     */
    public static function escapeOptionValue(string $value): string
    {
        $escaped = '';
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $character = $value[$i];
            if ($character === '\\' || $character === '"' || $character === "'" || $character === '#' || $character === '!') {
                $escaped .= '\\'.$character;
            } else {
                $escaped .= $character;
            }
        }

        return $escaped;
    }

    /**
     * Read a password back the way the MySQL client does: an unescaped # ends the line.
     */
    public static function passwordFromOptionFile(string $body): string
    {
        $password = null;
        foreach (preg_split("/\r\n|\n|\r/", $body) ?: [] as $line) {
            $visible = self::stripUnescapedHashComment($line);
            if (preg_match('/^\s*password\s*=\s*(.*)$/i', $visible, $matches) === 1) {
                $password = self::unquoteOptionValue(trim($matches[1]));
            }
        }

        if ($password === null) {
            throw new RuntimeException('Option file has no password entry.');
        }

        return $password;
    }

    private static function stripUnescapedHashComment(string $line): string
    {
        $visible = '';
        $length = strlen($line);
        for ($i = 0; $i < $length; $i++) {
            if ($line[$i] === '#' && ($i === 0 || $line[$i - 1] !== '\\')) {
                break;
            }
            $visible .= $line[$i];
        }

        return $visible;
    }

    private static function unquoteOptionValue(string $value): string
    {
        if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }

        $parsed = '';
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            if ($value[$i] === '\\' && $i + 1 < $length) {
                $parsed .= $value[++$i];
                continue;
            }
            $parsed .= $value[$i];
        }

        return $parsed;
    }
}
