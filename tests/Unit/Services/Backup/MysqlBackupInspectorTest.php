<?php

namespace Tests\Unit\Services\Backup;

use App\Services\Backup\MysqlBackupInspector;
use PHPUnit\Framework\TestCase;

class MysqlBackupInspectorTest extends TestCase
{
    public function test_coordinate_flag_follows_the_client_and_whether_logging_is_on(): void
    {
        $this->assertSame('--source-data=2', MysqlBackupInspector::coordinateFlag("usage: --source-data", true));
        $this->assertSame('--master-data=2', MysqlBackupInspector::coordinateFlag("usage: --master-data", true));
        $this->assertNull(MysqlBackupInspector::coordinateFlag("usage: --source-data", false));
        $this->assertNull(MysqlBackupInspector::coordinateFlag("no coordinate option", true));
    }

    public function test_privilege_error_is_limited_to_binary_log_grants(): void
    {
        $this->assertTrue(MysqlBackupInspector::isCoordinatePrivilegeError(
            'Access denied; you need (at least one of) the REPLICATION CLIENT privilege(s)'
        ));
        $this->assertFalse(MysqlBackupInspector::isCoordinatePrivilegeError(
            'Access denied for user \'school\'@\'localhost\' (using password: YES)'
        ));
    }

    public function test_dump_header_records_either_source_or_master_coordinates(): void
    {
        $source = "CHANGE REPLICATION SOURCE TO SOURCE_LOG_FILE='mysql-bin.000042', SOURCE_LOG_POS=154;";
        $this->assertSame(
            ['file' => 'mysql-bin.000042', 'position' => 154],
            MysqlBackupInspector::parseBinlogCoordinates($source)
        );

        $master = "CHANGE MASTER TO MASTER_LOG_FILE='mysql-bin.000003', MASTER_LOG_POS=107;";
        $this->assertSame(
            ['file' => 'mysql-bin.000003', 'position' => 107],
            MysqlBackupInspector::parseBinlogCoordinates($master)
        );
        $this->assertNull(MysqlBackupInspector::parseBinlogCoordinates('no coordinates'));
    }

    public function test_gzip_sql_and_binary_log_magic_are_checked_before_upload(): void
    {
        $dump = tempnam(sys_get_temp_dir(), 'erp-dump-');
        $this->assertNotFalse($dump);
        $gzip = gzopen($dump, 'wb');
        $this->assertNotFalse($gzip);
        gzwrite($gzip, "-- MySQL dump 10.13\n-- CHANGE REPLICATION SOURCE TO SOURCE_LOG_FILE='mysql-bin.000008', SOURCE_LOG_POS=220;\n");
        gzclose($gzip);

        $header = MysqlBackupInspector::readGzipHeader($dump);
        $this->assertSame(
            ['file' => 'mysql-bin.000008', 'position' => 220],
            MysqlBackupInspector::parseBinlogCoordinates($header)
        );

        $log = tempnam(sys_get_temp_dir(), 'erp-bin-');
        $this->assertNotFalse($log);
        file_put_contents($log, MysqlBackupInspector::BINLOG_MAGIC.'rest');
        MysqlBackupInspector::assertBinaryLog($log);

        $text = tempnam(sys_get_temp_dir(), 'erp-text-');
        $this->assertNotFalse($text);
        file_put_contents($text, 'this is not a binary log');
        $this->expectException(\RuntimeException::class);
        MysqlBackupInspector::assertBinaryLog($text);
    }
}
