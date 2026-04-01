<?php

namespace Tests\Unit\ExamReports;

use App\Exports\ClassSheetExport;
use PHPUnit\Framework\TestCase;

class ClassSheetExportTest extends TestCase
{
    public function test_array_includes_meta_rows_and_header_row_with_subject_columns(): void
    {
        $payload = [
            'subjects' => [
                ['id' => 10, 'code' => 'MTH', 'name' => 'Math'],
                ['id' => 11, 'code' => 'ENG', 'name' => 'English'],
            ],
            'rows' => [
                [
                    'admission_number' => 'A001',
                    'name' => 'Jane Doe',
                    'subject_scores' => [10 => 50, 11 => 60],
                    'subject_positions' => [10 => 1, 11 => 2],
                    'total' => 110,
                    'average' => 55,
                    'position' => 1,
                ],
            ],
        ];

        $export = new ClassSheetExport($payload, 'Test', 'Tester', 'Acme School');
        $rows = $export->array();

        $this->assertSame('Acme School', $rows[0][0]);
        $this->assertStringContainsString('Class Mark Sheet', (string) ($rows[1][0] ?? ''));
        $this->assertStringContainsString('Tester', (string) ($rows[2][0] ?? ''));
        $header = $rows[4];
        $this->assertSame(['#', 'Admission No', 'Student Name'], array_slice($header, 0, 3));
        $this->assertContains('MTH - Math', $header);
        $this->assertContains('ENG - English', $header);
        $this->assertSame('Total', $header[count($header) - 4]);
        $this->assertSame('Average', $header[count($header) - 3]);
        $this->assertSame('Class Pos', $header[count($header) - 2]);
        $this->assertSame('Stream Pos', $header[count($header) - 1]);
    }
}
