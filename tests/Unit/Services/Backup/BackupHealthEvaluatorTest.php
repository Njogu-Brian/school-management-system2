<?php

namespace Tests\Unit\Services\Backup;

use App\Services\Backup\BackupDrStatus;
use App\Services\Backup\BackupHealthEvaluator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BackupHealthEvaluatorTest extends TestCase
{
    public function test_fresh_dumps_and_a_recent_binary_log_pass(): void
    {
        $now = Carbon::parse('2026-10-14 10:00:00');
        $result = (new BackupHealthEvaluator)->evaluate([
            'frequent' => ['completed_at' => '2026-10-14 09:40:00'],
            'daily' => ['completed_at' => '2026-10-14 02:15:00'],
            'binlog' => ['disabled' => false, 'latest_archived' => 'mysql-bin.000010', 'archived_at' => '2026-10-14 09:56:00'],
        ], $now, 45, 10);

        $this->assertTrue($result['ok']);
    }

    public function test_binary_logging_off_fails_the_recovery_target(): void
    {
        $now = Carbon::parse('2026-10-14 10:00:00');
        $result = (new BackupHealthEvaluator)->evaluate([
            'frequent' => ['completed_at' => '2026-10-14 09:40:00'],
            'daily' => ['completed_at' => '2026-10-14 02:15:00'],
            'binlog' => ['disabled' => true, 'reason' => 'log_bin is OFF'],
        ], $now, 45, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_a_stale_archived_log_fails_even_when_dumps_are_fresh(): void
    {
        $now = Carbon::parse('2026-10-14 10:00:00');
        $result = (new BackupHealthEvaluator)->evaluate([
            'frequent' => ['completed_at' => '2026-10-14 09:40:00'],
            'daily' => ['completed_at' => '2026-10-14 02:15:00'],
            'binlog' => ['disabled' => false, 'latest_archived' => 'mysql-bin.000001', 'archived_at' => '2026-10-14 09:00:00'],
        ], $now, 45, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_status_file_round_trip(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'erp-status-');
        $this->assertNotFalse($path);
        @unlink($path);

        $status = new BackupDrStatus($path);
        $status->merge(['frequent' => ['completed_at' => '2026-10-14T09:40:00+03:00']]);
        $status->merge(['binlog' => ['disabled' => false, 'latest_archived' => 'mysql-bin.000002']]);

        $read = $status->read();
        $this->assertSame('2026-10-14T09:40:00+03:00', $read['frequent']['completed_at']);
        $this->assertSame('mysql-bin.000002', $read['binlog']['latest_archived']);
        @unlink($path);
    }
}
