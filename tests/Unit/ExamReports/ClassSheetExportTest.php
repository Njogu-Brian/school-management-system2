<?php

namespace Tests\Unit\ExamReports;

use App\Exports\ClassSheetExport;
use PHPUnit\Framework\TestCase;

class ClassSheetExportTest extends TestCase
{
    public function test_headings_include_subject_columns_and_summary_columns(): void
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

        $export = new ClassSheetExport($payload, 'Test');
        $headings = $export->headings();

        $this->assertSame(['#', 'Admission No', 'Student Name'], array_slice($headings, 0, 3));
        $this->assertContains('MTH - Math', $headings);
        $this->assertContains('MTH - Math Pos', $headings);
        $this->assertContains('ENG - English', $headings);
        $this->assertContains('ENG - English Pos', $headings);
        $this->assertSame('Total', $headings[count($headings) - 4]);
        $this->assertSame('Average', $headings[count($headings) - 3]);
        $this->assertSame('Class Pos', $headings[count($headings) - 2]);
        $this->assertSame('Stream Pos', $headings[count($headings) - 1]);
    }
}

