<?php

namespace Tests\Unit\Services\Finance;

use App\Services\Finance\FamilyPaymentSplitter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FamilyPaymentSplitterTest extends TestCase
{
    #[Test]
    public function it_splits_when_the_amount_matches_the_family_total(): void
    {
        $balances = [410 => 12650.0, 407 => 12650.0, 408 => 7350.0];
        $alloc = FamilyPaymentSplitter::allocations($balances, 32650.0, 410);

        $this->assertNotNull($alloc);
        $byId = [];
        foreach ($alloc as $row) {
            $byId[$row['student_id']] = $row['amount'];
        }
        $this->assertEqualsWithDelta(12650.0, $byId[410], 0.01);
        $this->assertEqualsWithDelta(12650.0, $byId[407], 0.01);
        $this->assertEqualsWithDelta(7350.0, $byId[408], 0.01);
    }

    #[Test]
    public function it_splits_trisha_family_total_matched_to_one_child(): void
    {
        $balances = [248 => 24150.0, 127 => 22150.0, 427 => 24150.0];
        $this->assertTrue(FamilyPaymentSplitter::shouldShare($balances, 70450.0, 248));

        $alloc = FamilyPaymentSplitter::allocations($balances, 70450.0, 248);
        $this->assertNotNull($alloc);
        $this->assertCount(3, $alloc);
        $this->assertEqualsWithDelta(70450.0, array_sum(array_column($alloc, 'amount')), 0.01);
    }

    #[Test]
    public function it_does_not_split_a_one_shilling_overpay(): void
    {
        $balances = [1 => 2000.0, 2 => 15000.0];

        $this->assertFalse(FamilyPaymentSplitter::shouldShare($balances, 2001.0, 1));
        $this->assertNull(FamilyPaymentSplitter::allocations($balances, 2001.0, 1));
    }

    #[Test]
    public function it_does_not_split_when_there_is_only_one_child_with_a_balance(): void
    {
        $balances = [1 => 20000.0, 2 => 0.0];

        $this->assertFalse(FamilyPaymentSplitter::shouldShare($balances, 20000.0, 1));
    }

    #[Test]
    public function it_splits_a_large_overpay_while_siblings_still_owe(): void
    {
        $balances = [410 => 12650.0, 407 => 12650.0, 408 => 7350.0];

        $this->assertTrue(FamilyPaymentSplitter::shouldShare($balances, 25000.0, 410));
        $alloc = FamilyPaymentSplitter::allocations($balances, 25000.0, 410);
        $this->assertNotNull($alloc);
        $this->assertGreaterThanOrEqual(2, count($alloc));
        $this->assertEqualsWithDelta(25000.0, array_sum(array_column($alloc, 'amount')), 0.01);
    }
}
