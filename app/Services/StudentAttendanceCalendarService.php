<?php

namespace App\Services;

use App\Models\SchoolDay;
use App\Models\Student;
use App\Models\Term;
use Carbon\Carbon;

/**
 * Central rules for class attendance vs school calendar and student enrolment (admission_date).
 */
class StudentAttendanceCalendarService
{
    /**
     * Date-only enrolment: admission_date, or created_at date for legacy rows.
     */
    public function effectiveEnrolmentDate(Student $student): Carbon
    {
        if ($student->admission_date) {
            return Carbon::parse($student->admission_date)->startOfDay();
        }

        return Carbon::parse($student->created_at)->timezone(config('app.timezone', 'UTC'))->startOfDay();
    }

    public function isValidSchoolDay(Carbon|string $date): bool
    {
        return SchoolDay::isSchoolDay($date);
    }

    /**
     * Whether attendance may be recorded for this student on this date (date-only).
     */
    public function canMarkAttendanceForDate(Student $student, Carbon|string $date): bool
    {
        $d = Carbon::parse($date)->startOfDay();
        $today = Carbon::today(config('app.timezone', 'UTC'));

        if ($d->gt($today)) {
            return false;
        }

        if ($d->lt($this->effectiveEnrolmentDate($student))) {
            return false;
        }

        return $this->isValidSchoolDay($d->toDateString());
    }

    /**
     * Expected school days in [start, end] for this student, optionally clipped to a term.
     * Counts days on or after effective enrolment within the intersected range.
     */
    public function expectedSchoolDaysBetween(
        Student $student,
        Carbon|string $startDate,
        Carbon|string $endDate,
        ?Term $term = null
    ): int {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($start->gt($end)) {
            return 0;
        }

        if ($term !== null && $term->opening_date && $term->closing_date) {
            $tOpen = Carbon::parse($term->opening_date)->startOfDay();
            $tClose = Carbon::parse($term->closing_date)->startOfDay();
            if ($start->lt($tOpen)) {
                $start = $tOpen->copy();
            }
            if ($end->gt($tClose)) {
                $end = $tClose->copy();
            }
        }

        $enrol = $this->effectiveEnrolmentDate($student);
        if ($end->lt($enrol)) {
            return 0;
        }
        if ($start->lt($enrol)) {
            $start = $enrol->copy();
        }

        if ($start->gt($end)) {
            return 0;
        }

        return SchoolDay::countSchoolDays($start->toDateString(), $end->toDateString());
    }

    /**
     * Whether a calendar day counts as "in session" for consecutive absence streaks (school days only, on/after enrolment).
     */
    public function isAttendanceSessionDayForStudent(Student $student, Carbon|string $date): bool
    {
        $d = Carbon::parse($date)->startOfDay();
        if ($d->lt($this->effectiveEnrolmentDate($student))) {
            return false;
        }

        return $this->isValidSchoolDay($d->toDateString());
    }

}
