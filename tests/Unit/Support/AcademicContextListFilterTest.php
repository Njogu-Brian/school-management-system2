<?php

namespace Tests\Unit\Support;

use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\AcademicCalendarService;
use App\Support\AcademicContext;
use Carbon\Carbon;
use Tests\TestCase;

class AcademicContextListFilterTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        AcademicCalendarService::flush();
        parent::tearDown();
    }

    public function test_list_defaults_to_last_closed_term_during_holiday_prep(): void
    {
        $year2026 = AcademicYear::query()->create(['year' => 2026, 'is_active' => true]);
        $year2027 = AcademicYear::query()->create(['year' => 2027, 'is_active' => false]);

        $closed = Term::query()->create([
            'name' => 'Term 3',
            'academic_year_id' => $year2026->id,
            'is_current' => false,
            'opening_date' => '2026-08-25',
            'closing_date' => '2026-10-30',
        ]);

        $upcoming = Term::query()->create([
            'name' => 'Term 1',
            'academic_year_id' => $year2027->id,
            'is_current' => false,
            'opening_date' => '2027-01-12',
            'closing_date' => '2027-04-03',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-15', config('app.timezone')));
        AcademicCalendarService::flush();

        $this->assertTrue($upcoming->is(app(AcademicCalendarService::class)->currentTerm()));
        $this->assertFalse(is_school_in_session());

        $this->assertSame((int) $year2026->id, AcademicContext::resolveListYearId());
        $this->assertSame((int) $closed->id, AcademicContext::resolveListTermId((int) $year2026->id));

        $filters = AcademicContext::listFilterIds(
            hasYearParam: false,
            yearFilled: false,
            yearValue: null,
            hasTermParam: false,
            termFilled: false,
            termValue: null,
        );

        $this->assertTrue($filters['applyYearFilter']);
        $this->assertTrue($filters['applyTermFilter']);
        $this->assertSame((int) $year2026->id, $filters['yearId']);
        $this->assertSame((int) $closed->id, $filters['termId']);
    }

    public function test_explicit_empty_year_means_all_years(): void
    {
        $year = AcademicYear::query()->create(['year' => 2026, 'is_active' => true]);
        Term::query()->create([
            'name' => 'Term 1',
            'academic_year_id' => $year->id,
            'is_current' => true,
            'opening_date' => '2026-01-12',
            'closing_date' => '2026-04-03',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-02-01', config('app.timezone')));
        AcademicCalendarService::flush();

        $filters = AcademicContext::listFilterIds(
            hasYearParam: true,
            yearFilled: false,
            yearValue: null,
            hasTermParam: true,
            termFilled: false,
            termValue: null,
        );

        $this->assertFalse($filters['applyYearFilter']);
        $this->assertFalse($filters['applyTermFilter']);
        $this->assertNull($filters['yearId']);
        $this->assertNull($filters['termId']);

        $view = AcademicContext::forView(
            requestedYearId: $filters['yearId'],
            requestedTermId: $filters['termId'],
            applyDefaults: false,
        );

        $this->assertNull($view['selectedYearId']);
        $this->assertNull($view['selectedTermId']);
    }

    public function test_filter_request_preserves_submitted_year_and_term(): void
    {
        $past = AcademicYear::query()->create(['year' => 2025, 'is_active' => false]);
        $current = AcademicYear::query()->create(['year' => 2026, 'is_active' => true]);

        $pastTerm = Term::query()->create([
            'name' => 'Term 2',
            'academic_year_id' => $past->id,
            'is_current' => false,
            'opening_date' => '2025-05-01',
            'closing_date' => '2025-07-31',
        ]);

        Term::query()->create([
            'name' => 'Term 1',
            'academic_year_id' => $current->id,
            'is_current' => true,
            'opening_date' => '2026-01-12',
            'closing_date' => '2026-04-03',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-02-01', config('app.timezone')));
        AcademicCalendarService::flush();

        $view = AcademicContext::forFilterRequest(
            hasYearParam: true,
            yearValue: (int) $past->id,
            hasTermParam: true,
            termValue: (int) $pastTerm->id,
        );

        $this->assertSame((int) $past->id, $view['selectedYearId']);
        $this->assertSame((int) $pastTerm->id, $view['selectedTermId']);
    }

    public function test_filter_request_defaults_to_last_closed_during_holiday(): void
    {
        $year2026 = AcademicYear::query()->create(['year' => 2026, 'is_active' => true]);
        $year2027 = AcademicYear::query()->create(['year' => 2027, 'is_active' => false]);

        $closed = Term::query()->create([
            'name' => 'Term 3',
            'academic_year_id' => $year2026->id,
            'is_current' => false,
            'opening_date' => '2026-08-25',
            'closing_date' => '2026-10-30',
        ]);

        Term::query()->create([
            'name' => 'Term 1',
            'academic_year_id' => $year2027->id,
            'is_current' => false,
            'opening_date' => '2027-01-12',
            'closing_date' => '2027-04-03',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-11-15', config('app.timezone')));
        AcademicCalendarService::flush();

        $view = AcademicContext::forFilterRequest(
            hasYearParam: false,
            yearValue: null,
            hasTermParam: false,
            termValue: null,
        );

        $this->assertSame((int) $year2026->id, $view['selectedYearId']);
        $this->assertSame((int) $closed->id, $view['selectedTermId']);
    }

    public function test_past_year_filter_is_preserved(): void
    {
        $past = AcademicYear::query()->create(['year' => 2025, 'is_active' => false]);
        $current = AcademicYear::query()->create(['year' => 2026, 'is_active' => true]);

        $pastTerm = Term::query()->create([
            'name' => 'Term 2',
            'academic_year_id' => $past->id,
            'is_current' => false,
            'opening_date' => '2025-05-01',
            'closing_date' => '2025-07-31',
        ]);

        Term::query()->create([
            'name' => 'Term 1',
            'academic_year_id' => $current->id,
            'is_current' => true,
            'opening_date' => '2026-01-12',
            'closing_date' => '2026-04-03',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-02-01', config('app.timezone')));
        AcademicCalendarService::flush();

        $filters = AcademicContext::listFilterIds(
            hasYearParam: true,
            yearFilled: true,
            yearValue: (int) $past->id,
            hasTermParam: true,
            termFilled: true,
            termValue: (int) $pastTerm->id,
        );

        $this->assertTrue($filters['applyYearFilter']);
        $this->assertTrue($filters['applyTermFilter']);
        $this->assertSame((int) $past->id, $filters['yearId']);
        $this->assertSame((int) $pastTerm->id, $filters['termId']);
    }
}
