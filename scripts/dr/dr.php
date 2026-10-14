<?php

use App\Services\Backup\BinlogArchiveGate;
use App\Services\Backup\BinlogLedger;
use App\Services\Backup\DrDumpArguments;
use App\Services\Backup\DumpSqlGuard;
use App\Services\Backup\EnvFile;
use App\Services\Backup\MysqlBackupInspector;
use App\Services\Backup\RestoreTargetGuard;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$command = $argv[1] ?? '';
$options = parse_options(array_slice($argv, 2));

try {
    match ($command) {
        'write-defaults' => write_defaults($options),
        'env-value' => env_value($options),
        'dump-args' => dump_args($options),
        'assert-dump' => DumpSqlGuard::assertFile(required($options, 'file')),
        'assert-replay' => DumpSqlGuard::assertReplayTargetsOnly(
            DumpSqlGuard::readSql(required($options, 'file')),
            required($options, 'target')
        ),
        'assert-target' => RestoreTargetGuard::assertTarget(
            required($options, 'target'),
            required($options, 'production')
        ),
        'coordinates' => coordinates(required($options, 'file')),
        'assert-binlog' => BinlogArchiveGate::assertComplete(
            required($options, 'file'),
            (int) required($options, 'size')
        ),
        'find-gaps' => find_gaps($options),
        'record-verified' => record_verified($options),
        'can-delete' => can_delete($options),
        default => throw new RuntimeException('Unknown dr.php command.'),
    };
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}

/**
 * @param  list<string>  $args
 * @return array<string, string>
 */
function parse_options(array $args): array
{
    $options = [];
    $count = count($args);
    for ($i = 0; $i < $count; $i++) {
        $arg = $args[$i];
        if (! str_starts_with($arg, '--')) {
            throw new RuntimeException('Unexpected argument.');
        }
        $name = substr($arg, 2);
        if ($i + 1 >= $count || str_starts_with($args[$i + 1], '--')) {
            throw new RuntimeException("Missing value for --{$name}.");
        }
        $options[$name] = $args[++$i];
    }

    return $options;
}

/**
 * @param  array<string, string>  $options
 */
function required(array $options, string $name): string
{
    $value = $options[$name] ?? '';
    if ($value === '') {
        throw new RuntimeException("Missing --{$name}.");
    }

    return $value;
}

/**
 * @param  array<string, string>  $options
 */
function write_defaults(array $options): void
{
    $env = required($options, 'env');
    $out = required($options, 'out');
    $written = MysqlBackupInspector::writeClientDefaultsFile(
        EnvFile::get($env, 'DB_USERNAME'),
        EnvFile::get($env, 'DB_PASSWORD'),
        EnvFile::get($env, 'DB_HOST') ?: '127.0.0.1',
        EnvFile::get($env, 'DB_PORT') ?: '3306',
    );
    $body = file_get_contents($written);
    @unlink($written);
    if ($body === false || file_put_contents($out, $body) === false) {
        throw new RuntimeException('Could not write the MySQL defaults file.');
    }
    @chmod($out, 0600);
}

/**
 * @param  array<string, string>  $options
 */
function env_value(array $options): void
{
    $key = required($options, 'key');
    $allowed = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'];
    if (! in_array($key, $allowed, true)) {
        throw new RuntimeException('Refusing to print that env value.');
    }
    $value = EnvFile::get(required($options, 'env'), $key);
    if ($key === 'DB_DATABASE' || $key === 'DB_USERNAME') {
        RestoreTargetGuard::assertIdentifier($value);
    }
    if ($key === 'DB_HOST' && preg_match('/^[A-Za-z0-9._-]+$/', $value) !== 1) {
        throw new RuntimeException('DB_HOST contains unsupported characters.');
    }
    if ($key === 'DB_PORT' && preg_match('/^[0-9]+$/', $value) !== 1) {
        throw new RuntimeException('DB_PORT must be numeric.');
    }
    fwrite(STDOUT, $value.PHP_EOL);
}

/**
 * @param  array<string, string>  $options
 */
function dump_args(array $options): void
{
    $help = (string) file_get_contents(required($options, 'help-file'));
    $arguments = DrDumpArguments::mysqldumpArgs(
        required($options, 'database'),
        required($options, 'defaults'),
        required($options, 'log-bin') === '1',
        $help,
    );
    foreach ($arguments as $argument) {
        fwrite(STDOUT, $argument.PHP_EOL);
    }
}

function coordinates(string $path): void
{
    $header = str_ends_with(strtolower($path), '.gz')
        ? MysqlBackupInspector::readGzipHeader($path)
        : DumpSqlGuard::readSql($path);
    $coordinates = MysqlBackupInspector::parseBinlogCoordinates($header);
    if ($coordinates === null) {
        throw new RuntimeException('Dump does not record a binary log filename and position.');
    }
    fwrite(STDOUT, $coordinates['file'].' '.$coordinates['position'].PHP_EOL);
}

/**
 * @param  array<string, string>  $options
 */
function find_gaps(array $options): void
{
    $missing = BinlogArchiveGate::missingSequence(
        csv_names($options['closed'] ?? ''),
        csv_names($options['verified'] ?? ''),
        required($options, 'coverage'),
    );
    foreach ($missing as $name) {
        fwrite(STDOUT, $name.PHP_EOL);
    }
    if ($missing !== []) {
        throw new RuntimeException('Binary log sequence has gaps. Archive failed closed.');
    }
}

/**
 * @return list<string>
 */
function csv_names(string $csv): array
{
    if (trim($csv) === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode(',', $csv)), fn ($name) => $name !== ''));
}

/**
 * @param  array<string, string>  $options
 */
function record_verified(array $options): void
{
    $size = (int) required($options, 'size');
    $remote = (int) required($options, 'remote-size');
    BinlogArchiveGate::assertReadyToRecord($size, $size, true, $remote);
    BinlogLedger::record(required($options, 'ledger'), required($options, 'name'), $size);
}

/**
 * @param  array<string, string>  $options
 */
function can_delete(array $options): void
{
    $allowed = BinlogLedger::deletionAllowed(
        required($options, 'ledger'),
        required($options, 'name'),
        (int) required($options, 'remote-size'),
    );
    if (! $allowed) {
        throw new RuntimeException('Local binary log was kept because its S3 copy is not verified at the ledger size.');
    }
}
