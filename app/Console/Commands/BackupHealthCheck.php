<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupDrStatus;
use App\Services\Backup\BackupHealthEvaluator;
use Illuminate\Console\Command;

class BackupHealthCheck extends Command
{
    protected $signature = 'backup:health';

    protected $description = 'Check that frequent dumps and archived binary logs are recent enough to meet the recovery target';

    public function handle(BackupDrStatus $status, BackupHealthEvaluator $evaluator): int
    {
        $required = (bool) config('backup.health_required', false);
        $result = $evaluator->evaluate(
            $status->read(),
            now(),
            (int) config('backup.frequent_max_age_minutes', 45),
            (int) config('backup.binlog_max_age_minutes', 10),
        );

        foreach ($result['lines'] as $line) {
            $label = $line['ok'] ? 'OK' : ($line['required'] ? 'FAIL' : 'WARN');
            $this->line("[{$label}] {$line['check']}: {$line['detail']}");
        }

        if ($result['ok']) {
            $this->info('Backup health checks passed.');

            return self::SUCCESS;
        }

        if (! $required) {
            $this->warn('Backup health checks failed, but BACKUP_HEALTH_REQUIRED is off. Enable it after the first daily dump and binary-log archive succeed.');

            return self::SUCCESS;
        }

        $this->error('Backup health checks failed.');

        return self::FAILURE;
    }
}
