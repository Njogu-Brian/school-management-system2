<?php

namespace App\Services\Backup;

use InvalidArgumentException;
use RuntimeException;

/**
 * mysqldump arguments for a dump that stays inside one database.
 * The database name is a positional argument, never --databases, so the file
 * does not contain CREATE DATABASE or USE.
 */
class DrDumpArguments
{
    /**
     * @return list<string>
     */
    public static function mysqldumpArgs(string $database, string $defaultsFile, bool $logBinEnabled, string $mysqldumpHelp): array
    {
        RestoreTargetGuard::assertIdentifier($database);
        if ($defaultsFile === '' || str_contains($defaultsFile, "\n")) {
            throw new InvalidArgumentException('Refusing an empty or multiline defaults-file path.');
        }

        $arguments = [
            '--defaults-extra-file='.$defaultsFile,
            '--single-transaction',
            '--quick',
            '--routines',
            '--events',
            '--triggers',
            '--default-character-set=utf8mb4',
        ];

        if (str_contains($mysqldumpHelp, '--set-gtid-purged')) {
            $arguments[] = '--set-gtid-purged=OFF';
        }

        if ($logBinEnabled) {
            $coordinateFlag = MysqlBackupInspector::coordinateFlag($mysqldumpHelp, true);
            if ($coordinateFlag === null) {
                throw new RuntimeException('mysqldump cannot record a binary log position. The dump was not taken.');
            }
            $arguments[] = $coordinateFlag;
        }

        $arguments[] = $database;

        if (in_array('--databases', $arguments, true) || in_array('-B', $arguments, true)) {
            throw new RuntimeException('Refusing to emit CREATE DATABASE or USE.');
        }

        return $arguments;
    }
}
