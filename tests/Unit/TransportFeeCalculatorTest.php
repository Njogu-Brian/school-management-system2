<?php

namespace Tests\Unit;

use App\Models\DropOffPoint;
use App\Services\TransportFeeCalculator;
use PHPUnit\Framework\TestCase;

class TransportFeeCalculatorTest extends TestCase
{
    public function test_same_point_uses_two_way_fare(): void
    {
        $point = new DropOffPoint([
            'name' => 'Kabete',
            'two_way_amount' => 8000,
            'one_way_amount' => 5000,
        ]);
        $point->id = 1;

        $result = TransportFeeCalculator::calculateFromPoints($point, $point);

        $this->assertTrue($result['can_calculate']);
        $this->assertSame(8000.0, $result['amount']);
        $this->assertSame('same_point_two_way', $result['breakdown']['formula']);
    }

    public function test_mixed_points_sum_half_of_each_two_way(): void
    {
        $kabete = new DropOffPoint([
            'name' => 'Kabete',
            'two_way_amount' => 8000,
            'one_way_amount' => 5000,
        ]);
        $kabete->id = 1;

        $wangige = new DropOffPoint([
            'name' => 'Wangige',
            'two_way_amount' => 6000,
            'one_way_amount' => 3500,
        ]);
        $wangige->id = 2;

        $result = TransportFeeCalculator::calculateFromPoints($kabete, $wangige);

        $this->assertTrue($result['can_calculate']);
        $this->assertSame(7000.0, $result['amount']);
        $this->assertSame('mixed_half_two_way', $result['breakdown']['formula']);
    }

    public function test_own_means_evening_uses_one_way_morning(): void
    {
        $kabete = new DropOffPoint([
            'name' => 'Kabete',
            'two_way_amount' => 8000,
            'one_way_amount' => 5000,
        ]);
        $kabete->id = 1;

        $own = new DropOffPoint([
            'name' => 'OWN MEANS',
            'two_way_amount' => 0,
            'one_way_amount' => 0,
        ]);
        $own->id = 99;

        $result = TransportFeeCalculator::calculateFromPoints($kabete, $own);

        $this->assertTrue($result['can_calculate']);
        $this->assertSame(5000.0, $result['amount']);
        $this->assertSame('own_means_one_way', $result['breakdown']['formula']);
    }

    public function test_both_own_means_is_zero(): void
    {
        $own = new DropOffPoint([
            'name' => 'OWN MEANS',
            'two_way_amount' => 0,
            'one_way_amount' => 0,
        ]);
        $own->id = 99;

        $result = TransportFeeCalculator::calculateFromPoints($own, $own);

        $this->assertTrue($result['can_calculate']);
        $this->assertSame(0.0, $result['amount']);
    }

    public function test_missing_two_way_rate_returns_error(): void
    {
        $point = new DropOffPoint([
            'name' => 'Kabete',
            'two_way_amount' => null,
            'one_way_amount' => 5000,
        ]);
        $point->id = 1;

        $result = TransportFeeCalculator::calculateFromPoints($point, $point);

        $this->assertFalse($result['can_calculate']);
        $this->assertNull($result['amount']);
        $this->assertNotEmpty($result['errors']);
    }
}
