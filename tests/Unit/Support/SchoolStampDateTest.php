<?php

namespace Tests\Unit\Support;

use App\Support\SchoolStampDate;
use Tests\TestCase;

class SchoolStampDateTest extends TestCase
{
    public function test_term_one_stamps_first_april_of_academic_year(): void
    {
        $date = SchoolStampDate::forReportCardTerm('Term 1', 2026);

        $this->assertSame('2026-04-01', $date->toDateString());
    }

    public function test_term_two_stamps_thirty_first_july(): void
    {
        $date = SchoolStampDate::forReportCardTerm('TERM 2', 2026);

        $this->assertSame('2026-07-31', $date->toDateString());
    }

    public function test_term_three_stamps_thirtieth_october(): void
    {
        $date = SchoolStampDate::forReportCardTerm(3, '2026');

        $this->assertSame('2026-10-30', $date->toDateString());
    }

    public function test_generated_stamp_uses_provided_datetime(): void
    {
        $date = SchoolStampDate::forGenerated('2026-11-11 11:12:00');

        $this->assertSame('2026-11-11', $date->toDateString());
    }
}
