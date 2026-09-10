<?php

namespace App\Services;

use App\Models\AssistantClassTeacherAssignment;
use App\Models\Attendance;
use App\Models\AttendanceReasonCode;
use App\Models\ClassTeacherAssignment;
use App\Models\SeniorTeacherClassroomAssignment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Parent/guardian absence reporting — extends existing Attendance rows (excused absent).
 * Does not invent a parallel student-leave system.
 */
class ParentAbsenceService
{
    public function __construct(
        protected StudentAttendanceCalendarService $calendar,
        protected AppChannelNotifyService $appChannel,
        protected ParentAppNotifyService $parentNotify,
    ) {}

    /**
     * Whether a parent may report an absence for this student/date.
     * Allows recent past (7 days) through near future (60 days) on valid school days only.
     */
    public function canReportAbsenceForDate(Student $student, Carbon|string $date): bool
    {
        $d = Carbon::parse($date)->startOfDay();
        $today = Carbon::today(config('app.timezone', 'UTC'));
        $min = $today->copy()->subDays(7);
        $max = $today->copy()->addDays(60);

        if ($d->lt($min) || $d->gt($max)) {
            return false;
        }

        if ($d->lt($this->calendar->effectiveEnrolmentDate($student))) {
            return false;
        }

        if ($this->calendar->wasArchivedOnDate($student, $d)) {
            return false;
        }

        return $this->calendar->isValidSchoolDay($d->toDateString());
    }

    /**
     * @return array{records: Collection<int, Attendance>, school_days: int, skipped: array<int, string>}
     */
    public function reportAbsence(
        Student $student,
        User $parent,
        string $startDate,
        string $endDate,
        string $reason,
        ?int $reasonCodeId = null,
    ): array {
        if (! $parent->canAccessStudent((int) $student->id)) {
            throw new InvalidArgumentException('You do not have access to this student.');
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        if ($end->lt($start)) {
            throw new InvalidArgumentException('End date must be on or after the start date.');
        }

        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) < 3) {
            throw new InvalidArgumentException('Please provide a reason for the absence.');
        }
        if (mb_strlen($reason) > 1000) {
            throw new InvalidArgumentException('Reason is too long.');
        }

        $reasonCode = null;
        if ($reasonCodeId) {
            $reasonCode = AttendanceReasonCode::query()->where('id', $reasonCodeId)->where('is_active', true)->first();
            if (! $reasonCode) {
                throw new InvalidArgumentException('Invalid absence reason code.');
            }
        }

        $period = CarbonPeriod::create($start, $end);
        $schoolDays = [];
        $skipped = [];
        foreach ($period as $day) {
            /** @var Carbon $day */
            if (! $this->canReportAbsenceForDate($student, $day)) {
                if (! $this->calendar->isValidSchoolDay($day->toDateString())) {
                    $skipped[] = $day->toDateString().' (not a school day)';
                } else {
                    $skipped[] = $day->toDateString().' (outside allowed dates)';
                }
                continue;
            }
            $schoolDays[] = $day->copy();
        }

        if ($schoolDays === []) {
            throw new InvalidArgumentException(
                'No valid school days in that range. Weekends, holidays, and closed days are skipped; choose school days within the allowed window.'
            );
        }

        $records = DB::transaction(function () use ($student, $parent, $schoolDays, $reason, $reasonCode) {
            $saved = collect();
            foreach ($schoolDays as $day) {
                $dateStr = $day->toDateString();
                $existing = Attendance::query()
                    ->where('student_id', $student->id)
                    ->whereDate('date', $dateStr)
                    ->whereNull('subject_id')
                    ->first();

                if ($existing && $existing->status === Attendance::STATUS_PRESENT) {
                    throw new InvalidArgumentException(
                        "{$dateStr} is already marked present by the school and cannot be changed by a parent."
                    );
                }

                $attendance = $existing ?? new Attendance([
                    'student_id' => $student->id,
                    'date' => $dateStr,
                ]);

                $attendance->fill([
                    'status' => Attendance::STATUS_ABSENT,
                    'reason' => $reasonCode?->name ?? $reason,
                    'reason_code_id' => $reasonCode?->id,
                    'is_excused' => true,
                    'is_medical_leave' => (bool) ($reasonCode?->is_medical ?? false),
                    'excuse_notes' => $reason,
                    'marked_by' => $parent->id,
                    'marked_at' => now(),
                ]);
                $attendance->save();
                $saved->push($attendance->fresh(['reasonCode']));
            }

            return $saved;
        });

        $this->notifyStaffOfParentAbsence($student, $parent, $start->toDateString(), $end->toDateString(), $reason, $records->count());
        $this->parentNotify->notifyParentsOfStudent(
            $student,
            'Absence reported',
            $this->parentConfirmationBody($student, $start->toDateString(), $end->toDateString(), $records->count()),
            [
                'type' => 'parent_absence_reported',
                'student_id' => $student->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
            $parent->id,
        );

        return [
            'records' => $records,
            'school_days' => $records->count(),
            'skipped' => $skipped,
        ];
    }

    /**
     * Recent parent-reported / excused absences for history UI.
     *
     * @return Collection<int, Attendance>
     */
    public function historyForStudent(Student $student, int $limit = 40): Collection
    {
        return Attendance::query()
            ->where('student_id', $student->id)
            ->where('status', Attendance::STATUS_ABSENT)
            ->where('is_excused', true)
            ->whereNull('subject_id')
            ->whereNotNull('excuse_notes')
            ->orderByDesc('date')
            ->limit($limit)
            ->with(['reasonCode', 'markedBy'])
            ->get();
    }

    /**
     * Relationship-scoped staff recipients for a parent absence report.
     *
     * @return Collection<int, User>
     */
    public function resolveStaffRecipients(Student $student): Collection
    {
        $userIds = collect();

        $classroomId = (int) ($student->classroom_id ?? 0);
        $streamId = $student->stream_id ? (int) $student->stream_id : null;

        if ($classroomId > 0) {
            $student->loadMissing('classroom');

            $staffIds = ClassTeacherAssignment::query()
                ->where('classroom_id', $classroomId)
                ->where(function ($q) use ($streamId) {
                    if ($streamId) {
                        $q->where('stream_id', $streamId)->orWhereNull('stream_id');
                    } else {
                        $q->whereNull('stream_id');
                    }
                })
                ->pluck('staff_id');

            if ($staffIds->isEmpty()) {
                $staffIds = ClassTeacherAssignment::query()
                    ->where('classroom_id', $classroomId)
                    ->pluck('staff_id');
            }

            if (Schema::hasTable('assistant_class_teacher_assignments')) {
                $assistant = AssistantClassTeacherAssignment::query()
                    ->where('classroom_id', $classroomId)
                    ->when($streamId, fn ($q) => $q->where(function ($inner) use ($streamId) {
                        $inner->where('stream_id', $streamId)->orWhereNull('stream_id');
                    }))
                    ->pluck('staff_id');
                $staffIds = $staffIds->merge($assistant);
            }

            // Legacy classroom.class_teacher_id fallback
            $legacy = $student->classroom?->class_teacher_id;
            if ($legacy) {
                $staffIds = $staffIds->push((int) $legacy);
            }

            $fromStaff = Staff::query()
                ->whereIn('id', $staffIds->unique()->filter()->values())
                ->whereNotNull('user_id')
                ->pluck('user_id');
            $userIds = $userIds->merge($fromStaff);

            // Explicit senior/deputy classroom assignments only (not all Senior Teacher roles).
            if (Schema::hasTable('senior_teacher_classroom_assignments')) {
                $seniorUserIds = SeniorTeacherClassroomAssignment::query()
                    ->where('classroom_id', $classroomId)
                    ->pluck('senior_teacher_id');
                $userIds = $userIds->merge($seniorUserIds);
            }
        }

        // Relevant school office / administrators (not all teachers / not all senior teachers).
        $office = User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Super Admin', 'Admin', 'Secretary', 'Director']);
            })
            ->pluck('id');
        $userIds = $userIds->merge($office);

        return User::query()
            ->whereIn('id', $userIds->unique()->filter()->values())
            ->get();
    }

    protected function notifyStaffOfParentAbsence(
        Student $student,
        User $parent,
        string $start,
        string $end,
        string $reason,
        int $dayCount,
    ): void {
        $recipients = $this->resolveStaffRecipients($student);
        if ($recipients->isEmpty()) {
            return;
        }

        $child = $student->full_name ?? 'a student';
        $range = $start === $end ? $start : "{$start} to {$end}";
        $title = 'Parent reported absence';
        $body = "{$child} — parent reported absence for {$range} ({$dayCount} school day".($dayCount === 1 ? '' : 's').'). Reason: '.$reason;

        $this->appChannel->notifyUsers($recipients, $title, $body, [
            'type' => 'parent_absence_reported',
            'student_id' => $student->id,
            'start_date' => $start,
            'end_date' => $end,
            'reported_by' => $parent->id,
        ]);
    }

    protected function parentConfirmationBody(Student $student, string $start, string $end, int $dayCount): string
    {
        $child = $student->full_name ?? 'Your child';
        $range = $start === $end ? $start : "{$start} to {$end}";

        return "{$child}: absence reported for {$range} ({$dayCount} school day".($dayCount === 1 ? '' : 's').'). The school has been notified.';
    }
}
