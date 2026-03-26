<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
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
