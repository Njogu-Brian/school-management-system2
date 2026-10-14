<?php

namespace Tests\Unit\Services\Backup;

use App\Services\Backup\BackupDrStatus;
use App\Services\Backup\BackupPublisher;
use App\Services\Backup\BinlogArchiveGate;
use App\Services\Backup\BinlogLedger;
use App\Services\Backup\DrDumpArguments;
use App\Services\Backup\DumpSqlGuard;
use App\Services\Backup\EnvFile;
use App\Services\Backup\MysqlBackupInspector;
use App\Services\Backup\RestoreTargetGuard;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DisasterRecoverySafetyTest extends TestCase
{
    private string $password = 'sec#ret"q\\z';

    public function test_password_containing_hash_roundtrips_without_being_printed(): void
    {
        $env = tempnam(sys_get_temp_dir(), 'erp-env-');
        $this->assertNotFalse($env);
        $quoted = str_replace(['\\', '"'], ['\\\\', '\\"'], $this->password);
        file_put_contents($env, "DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=school_management\nDB_USERNAME=backup_user\nDB_PASSWORD=\"{$quoted}\"\n");

        $parsed = EnvFile::get($env, 'DB_PASSWORD');
        $this->assertSame($this->password, $parsed);

        $path = MysqlBackupInspector::writeClientDefaultsFile('backup_user', $parsed, '127.0.0.1', '3306');
        $body = (string) file_get_contents($path);
        $this->assertStringContainsString('password="sec\#ret\"q\\\\z"', $body);
        $this->assertSame($this->password, MysqlBackupInspector::passwordFromOptionFile($body));

        $truncated = MysqlBackupInspector::passwordFromOptionFile("[client]\npassword=\"{$this->password}\"\n");
        $this->assertNotSame($this->password, $truncated);

        [$code, $stdout, $stderr] = $this->dr(['env-value', '--env', $env, '--key', 'DB_PASSWORD']);
        $this->assertSame(1, $code);
        $this->assertSame('', $stdout);
        $this->assertStringNotContainsString($this->password, $stderr);
        @unlink($path);
        @unlink($env);
    }

    public function test_dump_arguments_do_not_switch_databases_or_carry_the_password(): void
    {
        $args = DrDumpArguments::mysqldumpArgs(
            'school_management',
            '/tmp/client.cnf',
            true,
            "mysqldump --source-data --set-gtid-purged\n",
        );

        $this->assertNotContains('--databases', $args);
        $this->assertNotContains('-B', $args);
        $this->assertContains('--single-transaction', $args);
        $this->assertContains('--routines', $args);
        $this->assertContains('--events', $args);
        $this->assertContains('--triggers', $args);
        $this->assertContains('--source-data=2', $args);
        $this->assertSame('school_management', $args[array_key_last($args)]);
        $this->assertStringNotContainsString($this->password, implode("\n", $args));

        $sql = "-- dump\nCREATE TABLE students (id INT);\n";
        DumpSqlGuard::assertRestoreSafe($sql);
        $this->assertSame([], DumpSqlGuard::databaseStatements($sql));
    }

    public function test_malicious_dump_sql_is_rejected_before_any_client_would_run(): void
    {
        $sql = "SELECT 1;\nUSE school_management;\nCREATE DATABASE school_management;\n";
        try {
            DumpSqlGuard::assertRestoreSafe($sql);
            $this->fail('A dump containing USE was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('was not executed', $exception->getMessage());
        }

        $gzip = tempnam(sys_get_temp_dir(), 'erp-bad-').'.sql.gz';
        file_put_contents($gzip, gzencode("USE school_management;\n"));
        [$code, $stdout] = $this->dr(['assert-dump', '--file', $gzip]);
        $this->assertSame(1, $code);
        $this->assertSame('', $stdout);
        @unlink($gzip);
    }

    public function test_partial_binary_log_is_not_recorded_complete(): void
    {
        $log = tempnam(sys_get_temp_dir(), 'erp-partial-');
        $this->assertNotFalse($log);
        file_put_contents($log, MysqlBackupInspector::BINLOG_MAGIC.'partial');
        $ledger = sys_get_temp_dir().'/erp-ledger-'.uniqid('', true);
        $statusPath = sys_get_temp_dir().'/erp-status-'.uniqid('', true).'.json';

        try {
            BinlogArchiveGate::assertComplete($log, 100);
            $this->fail('A short binary log was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('not recorded as archived', $exception->getMessage());
        }

        $publisher = new BackupPublisher(new BackupDrStatus($statusPath));
        try {
            $publisher->publish($log, 'binlog', [
                'binlog_name' => 'binlog.000010',
                'expected_size' => 100,
            ]);
            $this->fail('Publisher accepted a partial binary log.');
        } catch (RuntimeException) {
            $this->assertFileDoesNotExist($statusPath);
            $this->assertFileDoesNotExist($ledger);
        }

        [$code] = $this->dr([
            'record-verified',
            '--ledger', $ledger,
            '--name', 'binlog.000010',
            '--size', '100',
            '--remote-size', '4',
        ]);
        $this->assertSame(1, $code);
        $this->assertFileDoesNotExist($ledger);
        @unlink($log);
    }

    public function test_missing_binary_log_sequence_is_detected(): void
    {
        $missing = BinlogArchiveGate::missingSequence(
            ['binlog.000010', 'binlog.000012'],
            ['binlog.000010'],
            'binlog.000010',
        );
        $this->assertSame(['binlog.000011'], $missing);

        [$code, $stdout] = $this->dr([
            'find-gaps',
            '--coverage', 'binlog.000010',
            '--closed', 'binlog.000010,binlog.000012',
            '--verified', 'binlog.000010',
        ]);
        $this->assertSame(1, $code);
        $this->assertStringContainsString('binlog.000011', $stdout);

        $this->assertSame(
            [],
            BinlogArchiveGate::missingSequence(['binlog.000246'], [], 'binlog.000246'),
        );
    }

    public function test_failed_upload_does_not_record_or_allow_deletion(): void
    {
        $ledger = sys_get_temp_dir().'/erp-ledger-'.uniqid('', true);
        $local = tempnam(sys_get_temp_dir(), 'erp-local-');
        $this->assertNotFalse($local);
        file_put_contents($local, MysqlBackupInspector::BINLOG_MAGIC.str_repeat('x', 20));

        try {
            BinlogArchiveGate::assertReadyToRecord(24, 24, false, null);
            $this->fail('A failed upload was ready to record.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('not recorded as archived', $exception->getMessage());
        }

        $this->assertFalse(BinlogLedger::deletionAllowed($ledger, 'binlog.000010', 24));
        [$code] = $this->dr([
            'can-delete',
            '--ledger', $ledger,
            '--name', 'binlog.000010',
            '--remote-size', '24',
        ]);
        $this->assertSame(1, $code);
        $this->assertFileExists($local);

        BinlogLedger::record($ledger, 'binlog.000010', 24);
        $this->assertFalse(BinlogLedger::deletionAllowed($ledger, 'binlog.000010', 10));
        $this->assertTrue(BinlogLedger::deletionAllowed($ledger, 'binlog.000010', 24));
        @unlink($local);
        @unlink($ledger);
    }

    public function test_restore_target_refuses_production_and_system_schemas(): void
    {
        foreach (['school_management', 'mysql', 'information_schema', 'performance_schema', 'sys', 'MySQL'] as $target) {
            try {
                RestoreTargetGuard::assertTarget($target, 'school_management');
                $this->fail("Accepted restore target {$target}");
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }

        RestoreTargetGuard::assertTarget('erp_restore', 'school_management');

        [$code] = $this->dr([
            'assert-target',
            '--target', 'school_management',
            '--production', 'school_management',
        ]);
        $this->assertSame(1, $code);
    }

    public function test_hourly_application_backup_is_unchanged(): void
    {
        $root = dirname(__DIR__, 4);
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/BackupRestoreController.php');
        $service = (string) file_get_contents($root.'/app/Services/DatabaseBackupService.php');
        $routes = (string) file_get_contents($root.'/routes/console.php');
        $dump = (string) file_get_contents($root.'/scripts/dr/mysql-dump.sh');

        $this->assertStringNotContainsString('BackupPublisher', $controller);
        $this->assertStringNotContainsString('--source-data', $controller);
        $this->assertStringNotContainsString('scripts/dr/mysql-dump.sh', $controller);
        $this->assertStringNotContainsString('scripts/dr/', $service);
        $this->assertStringContainsString('runScheduledIfDue', $routes);
        $this->assertStringContainsString('->hourly()', $routes);
        $this->assertStringNotContainsString('backup:health', $routes);
        $this->assertStringNotContainsString('archive-binlogs', $routes);
        $this->assertStringNotContainsString('--databases', $dump);
        $this->assertStringContainsString('does not delete the hourly Laravel dumps', $dump);
        $this->assertDoesNotMatchRegularExpression('/\b(find|rm)\b[^\n]*storage\/app\/backups/', $dump);
    }

    public function test_uploader_policy_is_limited_to_the_mysql_backup_prefix(): void
    {
        $root = dirname(__DIR__, 4);
        $uploader = json_decode((string) file_get_contents($root.'/deploy/dr/backup-uploader-policy.json'), true, 512, JSON_THROW_ON_ERROR);
        $admin = json_decode((string) file_get_contents($root.'/deploy/dr/backup-bucket-admin-policy.json'), true, 512, JSON_THROW_ON_ERROR);
        $lifecycle = json_decode((string) file_get_contents($root.'/deploy/dr/s3-lifecycle.json'), true, 512, JSON_THROW_ON_ERROR);

        $uploaderActions = [];
        foreach ($uploader['Statement'] as $statement) {
            $this->assertSame('Allow', $statement['Effect']);
            $prefixLimited = false;
            foreach ((array) $statement['Resource'] as $resource) {
                $prefixLimited = $prefixLimited || str_contains($resource, 'private/backups/mysql');
            }
            foreach ((array) ($statement['Condition']['StringLike']['s3:prefix'] ?? []) as $prefix) {
                $prefixLimited = $prefixLimited || str_contains($prefix, 'private/backups/mysql');
            }
            $this->assertTrue($prefixLimited, $statement['Sid'] ?? 'statement');
            foreach ((array) $statement['Action'] as $action) {
                $uploaderActions[] = $action;
            }
        }
        $this->assertNotContains('s3:DeleteObject', $uploaderActions);
        $this->assertNotContains('s3:PutBucketVersioning', $uploaderActions);
        $this->assertNotContains('s3:PutLifecycleConfiguration', $uploaderActions);

        $adminActions = [];
        foreach ($admin['Statement'] as $statement) {
            $actions = (array) $statement['Action'];
            foreach ($actions as $action) {
                $adminActions[] = $action;
            }
            $deletesBackupObjects = array_filter(
                $actions,
                fn (string $action): bool => str_contains($action, 'DeleteObject'),
            );
            if ($deletesBackupObjects !== []) {
                foreach ((array) $statement['Resource'] as $resource) {
                    $this->assertStringContainsString('private/backups/mysql/*', $resource);
                }
            }
        }
        $this->assertContains('s3:PutBucketVersioning', $adminActions);
        $this->assertContains('s3:PutLifecycleConfiguration', $adminActions);

        foreach ($lifecycle['Rules'] as $rule) {
            $prefix = $rule['Filter']['Prefix'];
            $this->assertStringStartsWith('private/backups/mysql/', $prefix);
            $this->assertNotSame('private/backups/mysql/', $prefix);
        }
    }

    public function test_restore_script_rejects_a_malicious_dump_before_mysql_runs(): void
    {
        $bash = $this->bashBinary();
        if ($bash === null) {
            $this->markTestSkipped('bash is not available; the restore execution-path test was not executed.');
        }

        $root = dirname(__DIR__, 4);
        $bashRoot = $this->bashPath($root);
        $php = $this->bashPath(PHP_BINARY);
        $command = [$bash, $bashRoot.'/tests/dr/restore-safety.sh', $bashRoot, $php];
        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $root);
        $this->assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        $this->assertSame(0, $code, $stdout."\n".$stderr);
        $this->assertStringNotContainsString($this->password, $stdout.$stderr);
    }

    /**
     * @param  list<string>  $arguments
     * @return array{0: int, 1: string, 2: string}
     */
    private function dr(array $arguments): array
    {
        $root = dirname(__DIR__, 4);
        $command = [PHP_BINARY, $root.'/scripts/dr/dr.php', ...$arguments];
        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $root);
        $this->assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }

    private function bashBinary(): ?string
    {
        $git = 'C:\\Program Files\\Git\\bin\\bash.exe';
        if (is_file($git)) {
            return $git;
        }

        $candidates = [
            'bash',
            'C:\\Program Files\\Git\\usr\\bin\\bash.exe',
        ];
        foreach ($candidates as $candidate) {
            if ($candidate === 'bash') {
                $found = shell_exec('where bash 2>NUL');
                if (is_string($found) && trim($found) !== '') {
                    return trim(strtok($found, "\r\n"));
                }
                continue;
            }
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function bashPath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
