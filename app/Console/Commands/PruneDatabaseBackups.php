<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class PruneDatabaseBackups extends Command
{
    protected $signature = 'backup:prune
                            {--all : Delete every backup file in the backup directory}
                            {--force : Required when using --all}
                            {--days= : Override retention days (default: config backup.retention_days)}';

    protected $description = 'Prune local database backups older than the retention period, or delete all with --all --force';

    public function handle(DatabaseBackupService $backups): int
    {
        if ($this->option('all')) {
            if (! $this->option('force')) {
                $this->error('Refusing to delete all backups without --force.');

                return self::FAILURE;
            }
            $n = $backups->deleteAll();
            $this->info("Deleted {$n} backup file(s) from {$backups->directory()}.");

            return self::SUCCESS;
        }

        $days = $this->option('days');
        $retention = $days !== null && $days !== ''
            ? max(1, (int) $days)
            : (int) config('backup.retention_days', 5);

        $n = $backups->pruneOlderThan($retention);
        $this->info("Pruned {$n} backup file(s) older than {$retention} day(s) in {$backups->directory()}.");

        return self::SUCCESS;
    }
}
