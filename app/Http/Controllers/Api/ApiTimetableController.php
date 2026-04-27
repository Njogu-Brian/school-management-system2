<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Term;
use App\Services\TimetableService;
use Illuminate\Http\Request;

class ApiTimetableController extends Controller
{
    /**
     * Teacher timetable derived from subject allocations (same source as web portal).
     */
    public function teacher(Request $request, int $staffId)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $ownStaffId = $user->staff?->id;
        $isPrivileged = $user->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
        if (! $isPrivileged && (int) $ownStaffId !== (int) $staffId) {
            abort(403, 'You can only view your own timetable.');
        }

        $termId = $request->integer('term_id') ?: null;
        [$yearId, $resolvedTermId] = $this->resolveAcademicContext($termId);

        $generated = TimetableService::generateForTeacher($staffId, $yearId, $resolvedTermId);
        $slots = [];
        foreach ($generated['schedule'] as $i => $row) {
            $subject = $row['subject'] ?? null;
            $classroom = $row['classroom'] ?? null;
            $day = $row['day'] ?? '';
            $slots[] = [
                'id' => $i + 1,
                'day' => is_string($day) ? $day : (string) $day,
                'start_time' => isset($row['start']) ? (string) $row['start'] : '08:00',
                'end_time' => isset($row['end']) ? (string) $row['end'] : '08:40',
                'subject_id' => $subject->id ?? 0,
                'subject_name' => $subject->name ?? '',
                'teacher_id' => $staffId,
                'teacher_name' => null,
                'room' => $classroom->name ?? ($classroom->code ?? null),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'class_name' => null,
                'academic_year_id' => $yearId,
                'term_id' => $resolvedTermId,
                'slots' => $slots,
            ],
        ]);
    }

    /**
     * Class timetable for a student (from their current classroom allocation).
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
        } elseif ($user->hasAnyRole(['Parent', 'Guardian'])) {
            if (! $user->canAccessStudent((int) $studentId)) {
                abort(403, 'You do not have access to this student.');
            }
        } else {
            abort(403, 'You cannot view this timetable.');
        }

        if (! $student->classroom_id) {
            abort(422, 'Student is not assigned to a class.');
        }

        $termId = $request->integer('term_id') ?: null;
        [$yearId, $resolvedTermId] = $this->resolveAcademicContext($termId);

        $generated = TimetableService::generateForClassroom((int) $student->classroom_id, $yearId, $resolvedTermId);

        $slots = [];
        $i = 0;
        foreach ($generated['timetable'] as $day => $periods) {
            foreach ($periods as $period => $data) {
                if (in_array($period, ['Break', 'Lunch'], true)) {
                    continue;
                }
                if (! is_array($data) || ($data['subject'] ?? null) === null) {
                    continue;
                }
                $subject = $data['subject'];
                $teacher = $data['teacher'] ?? null;
                $slots[] = [
                    'id' => ++$i,
                    'day' => is_string($day) ? $day : (string) $day,
                    'start_time' => isset($data['start']) ? (string) $data['start'] : '08:00',
                    'end_time' => isset($data['end']) ? (string) $data['end'] : '08:40',
                    'subject_id' => $subject->id ?? 0,
                    'subject_name' => $subject->name ?? '',
                    'teacher_id' => $teacher->id ?? 0,
                    'teacher_name' => $teacher ? ($teacher->full_name ?? null) : null,
                    'room' => $student->classroom->name ?? null,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'class_name' => $student->classroom->name ?? null,
                'academic_year_id' => $yearId,
                'term_id' => $resolvedTermId,
                'slots' => $slots,
            ],
        ]);
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
