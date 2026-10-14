<?php

namespace App\Services\Backup;

use Carbon\CarbonInterface;

/**
 * Decides whether the disaster-recovery pipeline is inside its time limits.
 * Snapshot backups older than the binary-log window restore only to that dump.
 */
class BackupHealthEvaluator
{
    /**
     * @param  array<string, mixed>  $status
     * @return array{ok: bool, lines: array<int, array{check: string, ok: bool, required: bool, detail: string}>}
     */
    public function evaluate(array $status, CarbonInterface $now, int $frequentMaxAgeMinutes, int $binlogMaxAgeMinutes): array
    {
        $lines = [];

        $frequent = is_array($status['frequent'] ?? null) ? $status['frequent'] : null;
        $lines[] = $this->ageCheck(
            'Frequent dump',
            $frequent['completed_at'] ?? null,
            $now,
            $frequentMaxAgeMinutes,
            true,
            'No frequent dump has been published. Run scripts/dr/mysql-dump.sh frequent.'
        );

        $daily = is_array($status['daily'] ?? null) ? $status['daily'] : null;
        $lines[] = $this->ageCheck(
            'Daily full backup',
            $daily['completed_at'] ?? null,
            $now,
            36 * 60,
            true,
            'No daily backup has been published. Run scripts/dr/mysql-dump.sh daily.'
        );

        $weekly = is_array($status['weekly'] ?? null) ? $status['weekly'] : null;
        $lines[] = $this->optionalAgeCheck('Weekly recovery copy', $weekly['completed_at'] ?? null, $now, 8 * 24 * 60, 'No weekly backup recorded yet.');

        $monthly = is_array($status['monthly'] ?? null) ? $status['monthly'] : null;
        $lines[] = $this->optionalAgeCheck('Monthly archive', $monthly['completed_at'] ?? null, $now, 35 * 24 * 60, 'No monthly backup recorded yet.');

        $lines[] = $this->binlogCheck(is_array($status['binlog'] ?? null) ? $status['binlog'] : null, $now, $binlogMaxAgeMinutes);

        $requiredFailed = false;
        foreach ($lines as $line) {
            if ($line['required'] && ! $line['ok']) {
                $requiredFailed = true;
                break;
            }
        }

        return [
            'ok' => ! $requiredFailed,
            'lines' => $lines,
        ];
    }

    /**
     * @return array{check: string, ok: bool, required: bool, detail: string}
     */
    private function ageCheck(
        string $check,
        mixed $completedAt,
        CarbonInterface $now,
        int $maxAgeMinutes,
        bool $required,
        string $missingDetail
    ): array {
        if (! is_string($completedAt) || $completedAt === '') {
            return [
                'check' => $check,
                'ok' => false,
                'required' => $required,
                'detail' => $missingDetail,
            ];
        }

        $age = $this->ageMinutes($completedAt, $now);
        $ok = $age <= $maxAgeMinutes;

        return [
            'check' => $check,
            'ok' => $ok,
            'required' => $required,
            'detail' => $ok
                ? "Completed {$age} minute(s) ago."
                : "Completed {$age} minute(s) ago, which is older than {$maxAgeMinutes} minutes.",
        ];
    }

    /**
     * @return array{check: string, ok: bool, required: bool, detail: string}
     */
    private function optionalAgeCheck(string $check, mixed $completedAt, CarbonInterface $now, int $maxAgeMinutes, string $missingDetail): array
    {
        if (! is_string($completedAt) || $completedAt === '') {
            return [
                'check' => $check,
                'ok' => true,
                'required' => false,
                'detail' => $missingDetail,
            ];
        }

        return $this->ageCheck($check, $completedAt, $now, $maxAgeMinutes, true, $missingDetail);
    }

    /**
     * @param  array<string, mixed>|null  $binlog
     * @return array{check: string, ok: bool, required: bool, detail: string}
     */
    private function binlogCheck(?array $binlog, CarbonInterface $now, int $maxAgeMinutes): array
    {
        if ($binlog === null) {
            return [
                'check' => 'Binary log archive',
                'ok' => false,
                'required' => true,
                'detail' => 'No binary-log archive status. Run scripts/dr/archive-binlogs.sh after binary logging is enabled.',
            ];
        }

        if (! empty($binlog['disabled'])) {
            return [
                'check' => 'Binary log archive',
                'ok' => false,
                'required' => true,
                'detail' => 'Binary logging is off, so recovery is limited to dump times. '.$this->stringOrEmpty($binlog['reason'] ?? null),
            ];
        }

        $archivedAt = $binlog['archived_at'] ?? null;
        if (! is_string($archivedAt) || $archivedAt === '') {
            return [
                'check' => 'Binary log archive',
                'ok' => false,
                'required' => true,
                'detail' => 'Binary logging is on, but no closed log has been archived to S3.',
            ];
        }

        $age = $this->ageMinutes($archivedAt, $now);
        $name = $this->stringOrEmpty($binlog['latest_archived'] ?? null);
        $ok = $age <= $maxAgeMinutes;

        return [
            'check' => 'Binary log archive',
            'ok' => $ok,
            'required' => true,
            'detail' => $ok
                ? "Latest archived log {$name} was stored {$age} minute(s) ago."
                : "Latest archived log {$name} was stored {$age} minute(s) ago, which is older than {$maxAgeMinutes} minutes.",
        ];
    }

    private function ageMinutes(string $completedAt, CarbonInterface $now): int
    {
        $completed = \Carbon\Carbon::parse($completedAt)->getTimestamp();
        $seconds = $now->getTimestamp() - $completed;

        return (int) floor($seconds / 60);
    }

    private function stringOrEmpty(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
