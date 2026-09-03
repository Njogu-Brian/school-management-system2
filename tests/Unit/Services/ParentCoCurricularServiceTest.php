<?php

namespace Tests\Unit\Services;

use App\Models\Votehead;
use App\Services\ParentCoCurricularService;
use Tests\TestCase;

class ParentCoCurricularServiceTest extends TestCase
{
    /** @test */
    public function yogurt_is_separated_from_activities(): void
    {
        $yogurt = new Votehead(['name' => 'Yoghurt', 'code' => 'YOGURT']);
        $ballet = new Votehead(['name' => 'Ballet', 'code' => 'BALLET']);
        $skating = new Votehead(['name' => 'Skating', 'code' => 'SKATE']);
        $music = new Votehead(['name' => 'Music', 'code' => 'MUSIC']);

        $this->assertTrue(ParentCoCurricularService::isYogurt($yogurt));
        $this->assertFalse(ParentCoCurricularService::isYogurt($ballet));
        $this->assertSame('yogurt', ParentCoCurricularService::iconKey($yogurt));
        $this->assertSame('ballet', ParentCoCurricularService::iconKey($ballet));
        $this->assertSame('skating', ParentCoCurricularService::iconKey($skating));
        $this->assertSame('music', ParentCoCurricularService::iconKey($music));
    }

    /** @test */
    public function upcoming_term_rolls_from_term_three_into_next_year(): void
    {
        $service = app(ParentCoCurricularService::class);
        $next = $service->upcomingPeriod(['year' => 2026, 'term' => 3, 'label' => 'Term 3 2026']);
        $this->assertSame(2027, $next['year']);
        $this->assertSame(1, $next['term']);

        $mid = $service->upcomingPeriod(['year' => 2026, 'term' => 1, 'label' => 'Term 1 2026']);
        $this->assertSame(2026, $mid['year']);
        $this->assertSame(2, $mid['term']);
    }
}
