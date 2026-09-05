<?php

namespace Tests\Unit\Services\Finance;

use App\Services\Finance\PaymentTermCoverage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaymentTermCoverageTest extends TestCase
{
    #[Test]
    public function it_flags_a_payment_that_covers_two_terms(): void
    {
        $coverage = PaymentTermCoverage::group([
            ['term_id' => 2, 'term_name' => 'Term 2', 'year' => 2026, 'opening_date' => '2026-05-01', 'amount' => 12650, 'invoice_number' => 'INV-T2'],
            ['term_id' => 3, 'term_name' => 'Term 3', 'year' => 2026, 'opening_date' => '2026-09-01', 'amount' => 20000, 'invoice_number' => 'INV-T3'],
        ], 3, '2026-09-01');

        $this->assertTrue($coverage['is_cross_term']);
        $this->assertSame(2, $coverage['term_count']);
        $this->assertSame('Term 2 (2026) + Term 3 (2026)', $coverage['summary_label']);
        $this->assertSame('previous', $coverage['terms'][0]['role']);
        $this->assertSame('Previous balance', $coverage['terms'][0]['role_label']);
        $this->assertSame('current', $coverage['terms'][1]['role']);
        $this->assertSame('Current invoice', $coverage['terms'][1]['role_label']);
        $this->assertEqualsWithDelta(12650.0, $coverage['terms'][0]['amount'], 0.01);
        $this->assertEqualsWithDelta(20000.0, $coverage['terms'][1]['amount'], 0.01);
    }

    #[Test]
    public function it_does_not_flag_a_single_term_payment(): void
    {
        $coverage = PaymentTermCoverage::group([
            ['term_id' => 3, 'term_name' => 'Term 3', 'year' => 2026, 'opening_date' => '2026-09-01', 'amount' => 15000, 'invoice_number' => 'INV-T3'],
            ['term_id' => 3, 'term_name' => 'Term 3', 'year' => 2026, 'opening_date' => '2026-09-01', 'amount' => 5000, 'invoice_number' => 'INV-T3B'],
        ], 3, '2026-09-01');

        $this->assertFalse($coverage['is_cross_term']);
        $this->assertSame(1, $coverage['term_count']);
        $this->assertSame('current', $coverage['terms'][0]['role']);
        $this->assertEqualsWithDelta(20000.0, $coverage['terms'][0]['amount'], 0.01);
        $this->assertSame(['INV-T3', 'INV-T3B'], $coverage['terms'][0]['invoice_numbers']);
    }

    #[Test]
    public function it_ignores_rows_without_a_term(): void
    {
        $coverage = PaymentTermCoverage::group([
            ['term_id' => 0, 'amount' => 1000],
            ['term_id' => null, 'amount' => 500],
        ]);

        $this->assertFalse($coverage['is_cross_term']);
        $this->assertSame(0, $coverage['term_count']);
        $this->assertSame('', $coverage['summary_label']);
    }
}
