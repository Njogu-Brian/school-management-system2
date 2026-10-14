<?php

namespace App\Services\Backup;

use RuntimeException;

/**
 * Reads a Laravel-style .env file without printing values.
 */
class EnvFile
{
    /**
     * @return array<string, string>
     */
    public static function parse(string $contents): array
    {
        $values = [];
        foreach (preg_split("/\r\n|\n|\r/", $contents) ?: [] as $line) {
            $trimmed = ltrim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (! str_contains($line, '=')) {
                continue;
            }
            [$key, $raw] = explode('=', $line, 2);
            $key = trim($key);
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) !== 1) {
                continue;
            }
            $values[$key] = self::parseValue($raw);
        }

        return $values;
    }

    public static function get(string $path, string $key): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("Env file not found: {$path}");
        }
        $values = self::parse((string) file_get_contents($path));
        if (! array_key_exists($key, $values) || $values[$key] === '') {
            throw new RuntimeException("Missing {$key} in the env file.");
        }

        return $values[$key];
    }

    public static function parseValue(string $raw): string
    {
        if ($raw !== '' && $raw[0] === '"') {
            $parsed = '';
            $length = strlen($raw);
            for ($i = 1; $i < $length; $i++) {
                $character = $raw[$i];
                if ($character === '\\' && $i + 1 < $length) {
                    $next = $raw[++$i];
                    $parsed .= match ($next) {
                        'n' => "\n",
                        'r' => "\r",
                        't' => "\t",
                        default => $next,
                    };
                    continue;
                }
                if ($character === '"') {
                    return $parsed;
                }
                $parsed .= $character;
            }

            throw new RuntimeException('Unclosed double-quoted env value.');
        }

        if ($raw !== '' && $raw[0] === "'") {
            $end = strpos($raw, "'", 1);
            if ($end === false) {
                throw new RuntimeException('Unclosed single-quoted env value.');
            }

            return substr($raw, 1, $end - 1);
        }

        $comment = strpos($raw, '#');
        if ($comment !== false) {
            $raw = substr($raw, 0, $comment);
        }

        return trim($raw);
    }
}
