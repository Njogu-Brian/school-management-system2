<?php

namespace Tests\Unit\ExamReports;

use App\Services\Academics\ExamReports\RowRanker;
use PHPUnit\Framework\TestCase;

class RowRankerTest extends TestCase
{
    public function test_ranks_by_total_desc_and_assigns_positions_with_ties(): void
    {
        $rows = collect([
            ['admission_number' => 'A003', 'total' => 100.0, 'average' => 50.0],
            ['admission_number' => 'A001', 'total' => 150.0, 'average' => 75.0],
            ['admission_number' => 'A002', 'total' => 150.0, 'average' => 74.0],
            ['admission_number' => 'A004', 'total' => null, 'average' => null],
        ]);

        $ranked = (new RowRanker())->rankByTotal($rows)->values()->all();

        $this->assertSame('A001', $ranked[0]['admission_number']);
        $this->assertSame(1, $ranked[0]['position']);

        $this->assertSame('A002', $ranked[1]['admission_number']);
        $this->assertSame(1, $ranked[1]['position'], 'Tie on total should share position');

        $this->assertSame('A003', $ranked[2]['admission_number']);
        $this->assertSame(3, $ranked[2]['position']);

        $this->assertSame('A004', $ranked[3]['admission_number']);
        $this->assertNull($ranked[3]['position'], 'Null totals should not be positioned');
    }
}

