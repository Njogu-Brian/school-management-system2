<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupPublisher;
use Illuminate\Console\Command;

class PublishDatabaseBackup extends Command
{
    protected $signature = 'backup:publish
                            {path? : Local gzip dump or closed binary log to verify and upload}
                            {--tier=frequent : frequent, daily, weekly, monthly, binlog, or ondemand}
                            {--mysql-version= : MySQL version to store in the manifest}
                            {--database= : Database name to store in the manifest}
                            {--binlog-name= : Closed binary log name, when --tier=binlog}
                            {--expected-size= : Byte size from SHOW BINARY LOGS, required for --tier=binlog}
                            {--binlog-disabled : Record that binary logging is off and do not upload}';

    protected $description = 'Verify a database dump or closed binary log, upload it to S3, and record recovery status';

    public function handle(BackupPublisher $publisher): int
    {
        if ($this->option('binlog-disabled')) {
            $publisher->markBinlogDisabled('MySQL log_bin is OFF. Point-in-time recovery is not active.');
            $this->warn('Binary logging is off. Dumps can still be restored, but only to the time each dump was taken.');

            return self::SUCCESS;
        }

        $path = $this->argument('path');
        if (! is_string($path) || $path === '') {
            $this->error('Provide the backup file path, or pass --binlog-disabled.');

            return self::FAILURE;
        }

        $context = [];
        foreach (['mysql-version' => 'mysql_version', 'database' => 'database', 'binlog-name' => 'binlog_name'] as $option => $key) {
            $value = $this->option($option);
            if (is_string($value) && $value !== '') {
                $context[$key] = $value;
            }
        }
        $expectedSize = $this->option('expected-size');
        if ($expectedSize !== null && $expectedSize !== '') {
            $context['expected_size'] = (int) $expectedSize;
        }

        $manifest = $publisher->publish($path, (string) $this->option('tier'), $context);

        $this->info('Published '.$manifest['tier'].' backup '.$manifest['filename']);
        if (! empty($manifest['s3_key'])) {
            $this->line('S3 key: '.$manifest['s3_key']);
        }
        if (! empty($manifest['binlog_file'])) {
            $this->line('Binary log start: '.$manifest['binlog_file'].' at '.$manifest['binlog_position']);
        }
        $this->line('SHA256: '.$manifest['sha256']);

        return self::SUCCESS;
    }
}
