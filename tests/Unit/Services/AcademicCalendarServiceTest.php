<?php

namespace Tests\Unit\Services;

use App\Models\AcademicYear;
use App\Models\SchoolDay;
use App\Models\Term;
use App\Services\AcademicCalendarService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Tests\TestCase;

class AcademicCalendarServiceTest extends TestCase
{
    private AcademicYear $year;

    private Term $term2;

    private Term $term3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->year = AcademicYear::factory()->create([
            'year' => 2026,
            'is_active' => true,
        ]);

        $this->term2 = Term::query()->create([
            'name' => 'Term 2',
            'academic_year_id' => $this->year->id,
            'is_current' => true,
            'opening_date' => '2026-04-28',
            'closing_date' => '2026-07-31',
        ]);

        $this->term3 = Term::query()->create([
            'name' => 'Term 3',
            'academic_year_id' => $this->year->id,
            'is_current' => false,
            'opening_date' => '2026-08-25',
            'closing_date' => '2026-10-30',
        ]);

        AcademicCalendarService::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        AcademicCalendarService::flush();
        parent::tearDown();
    }

    public function test_in_session_term_stays_current(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-10', config('app.timezone')));
        AcademicCalendarService::flush();

        $term = app(AcademicCalendarService::class)->currentTerm();

        $this->assertNotNull($term);
        $this->assertTrue($term->is($this->term2));
        $this->assertTrue(is_school_in_session());
        $this->assertTrue(SchoolDay::isSchoolDay('2026-06-10'));
    }

    public function test_holiday_break_moves_current_term_to_upcoming_but_school_is_closed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24', config('app.timezone')));
        AcademicCalendarService::flush();

        $term = app(AcademicCalendarService::class)->currentTerm();

        $this->assertNotNull($term);
        $this->assertTrue($term->is($this->term3));
        $this->assertFalse(is_school_in_session());
        $this->assertFalse(SchoolDay::isSchoolDay('2026-08-24'));

        $this->term2->refresh();
        $this->term3->refresh();
        $this->assertFalse((bool) $this->term2->is_current);
        $this->assertTrue((bool) $this->term3->is_current);
    }

    public function test_opening_day_is_a_school_day_and_in_session(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25', config('app.timezone')));
        AcademicCalendarService::flush();

        $term = app(AcademicCalendarService::class)->currentTerm();

        $this->assertTrue($term->is($this->term3));
        $this->assertTrue(is_school_in_session());
        $this->assertTrue(SchoolDay::isSchoolDay('2026-08-25'));
    }

    public function test_midterm_break_inside_term_is_not_a_school_day(): void
    {
        $this->term2->update([
            'midterm_start_date' => '2026-06-08',
            'midterm_end_date' => '2026-06-12',
        ]);
        AcademicCalendarService::flush();

        $this->assertFalse(SchoolDay::isSchoolDay('2026-06-10'));
        $this->assertFalse(is_school_in_session('2026-06-10'));
        $this->assertTrue(SchoolDay::isSchoolDay('2026-06-15'));
    }

    public function test_invoice_due_date_stays_term_opening_day_during_holiday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24', config('app.timezone')));
        AcademicCalendarService::flush();

        $this->assertTrue(get_current_term_model()->is($this->term3));
        $this->assertSame('2026-08-25', $this->term3->opening_date->toDateString());

        $student = $this->createStudent();
        $invoice = InvoiceService::ensure($student->id, 2026, 3);

        $this->assertNotNull($invoice->due_date);
        $this->assertSame('2026-08-25', $invoice->due_date->toDateString());
    }
}
