<?php

namespace App\Services;

use App\Models\SchoolDay;
use App\Models\Student;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

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

        // Students must not be markable on days they were archived (between an
        // archive event and the matching restore event).
        if ($this->wasArchivedOnDate($student, $d)) {
            return false;
        }

        return $this->isValidSchoolDay($d->toDateString());
    }

    /**
     * Archived intervals for a student, rebuilt from the archive_audits trail.
     *
     * Returns an array of ['start' => Carbon, 'end' => Carbon|null] pairs where
     * start is the archive day (inclusive) and end is the restore day (exclusive;
     * the student is markable again from the restore day). A null end means the
     * student is still archived (open interval).
     *
     * Archives that predate the audit trail cannot be reconstructed and are
     * ignored unless the student is still archived, in which case
     * students.archived_at is used as the interval start.
     *
     * @return array<int, array{start: Carbon, end: ?Carbon}>
     */
    public function archivedIntervals(Student $student): array
    {
        if (! isset($this->intervalCache[$student->id])) {
            $intervals = [];

            if (Schema::hasTable('archive_audits')) {
                $events = \App\Models\ArchiveAudit::query()
                    ->where('student_id', $student->id)
                    ->whereIn('action', ['archive', 'restore'])
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get(['action', 'created_at']);

                $open = null;
                foreach ($events as $event) {
                    if ($event->action === 'archive' && $open === null) {
                        $open = Carbon::parse($event->created_at)->startOfDay();
                    } elseif ($event->action === 'restore' && $open !== null) {
                        // Restore day itself counts as back-in-school (markable).
                        $intervals[] = ['start' => $open, 'end' => Carbon::parse($event->created_at)->startOfDay()];
                        $open = null;
                    }
                }

                if ($open !== null) {
                    // Archived but never restored.
                    $intervals[] = ['start' => $open, 'end' => null];
                }
            }

            // Fallback: currently archived without any audit trail.
            if ($intervals === [] && ($student->archive ?? false) && $student->archived_at) {
                $intervals[] = ['start' => Carbon::parse($student->archived_at)->startOfDay(), 'end' => null];
            }

            $this->intervalCache[$student->id] = $intervals;
        }

        return $this->intervalCache[$student->id];
    }

    /**
     * Whether the student was archived at any point on the given date.
     */
    public function wasArchivedOnDate(Student $student, Carbon|string $date): bool
    {
        $d = Carbon::parse($date)->startOfDay();

        foreach ($this->archivedIntervals($student) as $interval) {
            if ($d->lt($interval['start'])) {
                continue;
            }
            if ($interval['end'] === null || $d->lt($interval['end'])) {
                return true;
            }
        }

        return false;
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

        $days = SchoolDay::countSchoolDays($start->toDateString(), $end->toDateString());

        // Subtract school days that fall inside archived intervals: a student
        // cannot be expected at school while archived.
        foreach ($this->archivedIntervals($student) as $interval) {
            $iStart = $interval['start']->gt($start) ? $interval['start']->copy() : $start->copy();
            // Open interval (still archived) runs to the end of the range; a
            // closed interval ends the day BEFORE the restore day.
            $iEnd = $interval['end'] === null
                ? $end->copy()
                : $interval['end']->copy()->subDay();
            if ($iEnd->gt($end)) {
                $iEnd = $end->copy();
            }
            if ($iStart->lte($iEnd)) {
                $days -= SchoolDay::countSchoolDays($iStart->toDateString(), $iEnd->toDateString());
            }
        }

        return max(0, $days);
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

        if ($this->wasArchivedOnDate($student, $d)) {
            return false;
        }

        return $this->isValidSchoolDay($d->toDateString());
    }

    /** @var array<int, array<int, array{start: Carbon, end: ?Carbon}>> Per-request cache of archived intervals. */
    private array $intervalCache = [];

}
