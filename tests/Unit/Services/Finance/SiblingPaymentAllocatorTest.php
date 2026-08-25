<?php

namespace Tests\Unit\Services\Finance;

use App\Services\Finance\SiblingPaymentAllocator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SiblingPaymentAllocatorTest extends TestCase
{
    #[Test]
    public function it_clears_every_child_when_the_parent_pays_the_exact_total(): void
    {
        $balances = [1 => 25000.0, 2 => 23500.0, 3 => 21000.0];
        $alloc = SiblingPaymentAllocator::allocate($balances, 69500.0);

        $this->assertSame(25000.0, $alloc[1]);
        $this->assertSame(23500.0, $alloc[2]);
        $this->assertSame(21000.0, $alloc[3]);
    }

    #[Test]
    public function it_fully_clears_two_children_when_the_shortfall_fits_on_one_invoice(): void
    {
        $balances = [1 => 25000.0, 2 => 23500.0, 3 => 21000.0];
        $alloc = SiblingPaymentAllocator::allocate($balances, 50000.0);

        $this->assertEqualsWithDelta(5500.0, $alloc[1], 0.01);
        $this->assertEqualsWithDelta(23500.0, $alloc[2], 0.01);
        $this->assertEqualsWithDelta(21000.0, $alloc[3], 0.01);
        $this->assertEqualsWithDelta(50000.0, array_sum($alloc), 0.01);
    }

    #[Test]
    public function it_splits_a_partial_payment_equally_in_round_tens(): void
    {
        $balances = [1 => 25000.0, 2 => 23500.0, 3 => 21000.0];
        $alloc = SiblingPaymentAllocator::allocate($balances, 30000.0);

        $this->assertEqualsWithDelta(10000.0, $alloc[1], 0.01);
        $this->assertEqualsWithDelta(10000.0, $alloc[2], 0.01);
        $this->assertEqualsWithDelta(10000.0, $alloc[3], 0.01);
    }

    #[Test]
    public function it_leaves_a_600_balance_on_one_child_when_paying_50000_of_50600(): void
    {
        $balances = [1 => 25000.0, 2 => 23500.0, 3 => 2100.0];
        $alloc = SiblingPaymentAllocator::allocate($balances, 50000.0);

        $this->assertEqualsWithDelta(24400.0, $alloc[1], 0.01);
        $this->assertEqualsWithDelta(23500.0, $alloc[2], 0.01);
        $this->assertEqualsWithDelta(2100.0, $alloc[3], 0.01);
        $this->assertEqualsWithDelta(600.0, 25000.0 - $alloc[1], 0.01);
    }

    #[Test]
    public function it_rounds_uneven_equal_splits_to_the_nearest_10(): void
    {
        $balances = [1 => 20000.0, 2 => 20000.0, 3 => 20000.0];
        $alloc = SiblingPaymentAllocator::allocate($balances, 25000.0);

        foreach ($alloc as $amount) {
            $this->assertSame(0.0, fmod($amount, 10.0));
        }
        $this->assertEqualsWithDelta(25000.0, array_sum($alloc), 0.01);
    }
}
