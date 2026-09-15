<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Academics\Timetable;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Http\Request;

class ApiTimetableController extends Controller
{
    /**
     * Personal teaching timetable from saved portal rows.
     */
    public function mine(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $staffId = (int) ($user->staff?->id ?? 0);
        if ($staffId <= 0) {
            abort(422, 'Staff profile is not linked.');
        }

        return $this->slotsResponse($request, fn ($q) => $q->where('staff_id', $staffId));
    }

    /**
     * Full class grid. Homeroom teachers: own classes only. Admins: any class.
     */
    public function classGrid(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $classroomId = $request->integer('classroom_id');
        if ($classroomId <= 0) {
            abort(422, 'classroom_id is required.');
        }

        $isPrivileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator'])
            || $user->isSeniorTeacherUser()
            || $user->isDeputySeniorTeacherUser();

        if (! $isPrivileged && ! $user->canMarkClassAttendanceForClassroom($classroomId)) {
            abort(403, 'Class timetables are available for your homeroom classes only.');
        }

        return $this->slotsResponse($request, fn ($q) => $q->where('classroom_id', $classroomId), $classroomId);
    }

    /**
     * Teacher timetable derived from saved timetable rows (same source as the web portal).
     */
    public function teacher(Request $request, int $staffId)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $ownStaffId = $user->staff?->id;
        $isPrivileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Academic Administrator']);
        if (! $isPrivileged && (int) $ownStaffId !== (int) $staffId) {
            abort(403, 'You can only view your own timetable.');
        }

        return $this->slotsResponse($request, fn ($q) => $q->where('staff_id', $staffId));
    }

    /**
     * Class timetable for a student from saved rows for their classroom.
     */
    public function student(Request $request, int $studentId)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $student = Student::with(['classroom'])->findOrFail($studentId);

        if ($user->hasAnyRole(['Super Admin', 'Admin', 'Secretary'])) {
            // full access
        } elseif ($user->hasTeacherLikeRole()) {
            $query = Student::query()->where('id', $studentId)->where('archive', 0)->where('is_alumni', false);
            $user->applyTeacherStudentFilter($query);
            if (! $query->exists()) {
                abort(403, 'You do not have access to this student.');
            }
        } elseif ($user->shouldScopeAsParent()) {
            if (! $user->canAccessStudent((int) $studentId)) {
                abort(403, 'You do not have access to this student.');
            }
        } else {
            abort(403, 'You cannot view this timetable.');
        }

        if (! $student->classroom_id) {
            abort(422, 'Student is not assigned to a class.');
        }

        return $this->slotsResponse(
            $request,
            fn ($q) => $q->where('classroom_id', (int) $student->classroom_id),
            (int) $student->classroom_id,
            $student->classroom->name ?? null
        );
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder): mixed  $constrain
     */
    protected function slotsResponse(Request $request, callable $constrain, ?int $classroomId = null, ?string $className = null)
    {
        $termId = $request->integer('term_id') ?: null;
        [$yearId, $resolvedTermId] = $this->resolveAcademicContext($termId);

        $query = Timetable::query()
            ->with(['subject', 'teacher', 'classroom'])
            ->where('academic_year_id', $yearId)
            ->where('term_id', $resolvedTermId)
            ->orderBy('day')
            ->orderBy('period')
            ->orderBy('start_time');

        $constrain($query);

        $rows = $query->get();
        $slots = $rows->map(fn (Timetable $row) => $this->formatSlot($row))->values()->all();

        if ($className === null && $classroomId) {
            $className = $rows->first()?->classroom?->name;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'class_name' => $className,
                'academic_year_id' => $yearId,
                'term_id' => $resolvedTermId,
                'slots' => $slots,
            ],
        ]);
    }

    protected function formatSlot(Timetable $row): array
    {
        $teacher = $row->teacher;
        $start = $row->start_time;
        $end = $row->end_time;
        if ($start instanceof \DateTimeInterface) {
            $start = $start->format('H:i');
        }
        if ($end instanceof \DateTimeInterface) {
            $end = $end->format('H:i');
        }

        $isBreak = (bool) $row->is_break;
        $subjectName = $row->subject?->name ?? '';
        if ($isBreak && $subjectName === '') {
            $subjectName = 'Break';
        }

        return [
            'id' => (int) $row->id,
            'day' => (string) $row->day,
            'period' => (int) ($row->period ?? 0),
            'start_time' => (string) ($start ?: '08:00'),
            'end_time' => (string) ($end ?: '08:40'),
            'subject_id' => (int) ($row->subject_id ?? 0),
            'subject_name' => $subjectName,
            'teacher_id' => $row->staff_id ? (int) $row->staff_id : null,
            'teacher_name' => $teacher?->full_name ?? $teacher?->name ?? null,
            'room' => $row->room ?: ($row->classroom?->name ?? null),
            'classroom_id' => $row->classroom_id ? (int) $row->classroom_id : null,
            'classroom_name' => $row->classroom?->name ?? null,
            'is_break' => $isBreak,
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    protected function resolveAcademicContext(?int $termId): array
    {
        $year = AcademicYear::query()->where('is_active', true)->first()
            ?? AcademicYear::query()->orderByDesc('id')->first();

        if (! $year) {
            abort(422, 'No academic year configured.');
        }

        if ($termId) {
            $term = Term::query()->findOrFail($termId);
        } else {
            $term = Term::query()
                ->where('academic_year_id', $year->id)
                ->where('is_current', true)
                ->first()
                ?? Term::query()->where('academic_year_id', $year->id)->orderBy('opening_date')->first()
                ?? Term::query()->orderByDesc('opening_date')->first();
        }

        if (! $term) {
            abort(422, 'No term configured.');
        }

        return [(int) $year->id, (int) $term->id];
    }
}
