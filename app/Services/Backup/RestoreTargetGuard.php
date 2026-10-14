<?php

namespace App\Services\Backup;

use InvalidArgumentException;
use RuntimeException;

class RestoreTargetGuard
{
    private const SYSTEM_SCHEMAS = ['mysql', 'information_schema', 'performance_schema', 'sys'];

    public static function assertIdentifier(string $database): void
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $database) !== 1) {
            throw new InvalidArgumentException('Database name must contain only letters, numbers, and underscores.');
        }
    }

    public static function assertTarget(string $target, string $productionDatabase): void
    {
        self::assertIdentifier($target);
        self::assertIdentifier($productionDatabase);

        if (strcasecmp($target, $productionDatabase) === 0) {
            throw new RuntimeException("Refusing to restore into the live database {$productionDatabase}.");
        }

        foreach (self::SYSTEM_SCHEMAS as $schema) {
            if (strcasecmp($target, $schema) === 0) {
                throw new RuntimeException("Refusing to restore into the system schema {$target}.");
            }
        }
    }
}
