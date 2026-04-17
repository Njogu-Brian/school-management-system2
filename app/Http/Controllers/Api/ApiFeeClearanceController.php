<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentTermFeeClearance;
use App\Models\Term;
use App\Services\FeeClearanceStatusService;
use Illuminate\Http\Request;

class ApiFeeClearanceController extends Controller
{
    public function __construct(protected FeeClearanceStatusService $service)
    {
    }

    public function show(Request $request, int $id)
    {
        $request->validate([
            'term_id' => 'nullable|exists:terms,id',
        ]);

        $term = $request->term_id
            ? Term::find($request->term_id)
            : Term::where('is_current', true)->orderByDesc('id')->first();

        if (!$term) {
            return response()->json(['success' => false, 'message' => 'No term found.'], 422);
        }

        $student = Student::where('id', $id)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        $snapshot = $this->service->upsertSnapshot($student, $term);

        return response()->json([
            'success' => true,
            'data' => $this->toPublicPayload($snapshot),
        ]);
    }

    /**
     * Fee clearance roster for a class/stream (for teachers/drivers mobile).
     * Only returns status info (no amounts).
     */
    public function classRoster(Request $request, int $classId)
    {
        $request->validate([
            'term_id' => 'nullable|exists:terms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $term = $request->term_id
            ? Term::find($request->term_id)
            : Term::where('is_current', true)->orderByDesc('id')->first();

        if (!$term) {
            return response()->json(['success' => false, 'message' => 'No term found.'], 422);
        }

        $user = $request->user();
        if ($user && $user->hasTeacherLikeRole()) {
            if (! $user->canTeacherAccessClassroom($classId)) {
                return response()->json(['success' => false, 'message' => 'You are not assigned to this class.'], 403);
            }
        }

        $streamId = $request->stream_id ? (int) $request->stream_id : null;

        $studentQuery = Student::where('classroom_id', $classId)
            ->where('archive', 0)
            ->where('is_alumni', false);

        if ($streamId !== null) {
            $studentQuery->where('stream_id', $streamId);
        }
        if ($user && $user->hasTeacherLikeRole()) {
            $user->applyTeacherStudentFilter($studentQuery);
        }

        $students = $studentQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $snapshots = StudentTermFeeClearance::where('term_id', $term->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $data = $students->map(function ($student) use ($term, $snapshots) {
            $snapshot = $snapshots->get($student->id);
            if (!$snapshot) {
                $snapshot = $this->service->upsertSnapshot($student, $term);
            }

            return array_merge([
                'student_id' => $student->id,
                'name' => $student->full_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
                'admission_number' => $student->admission_number ?? null,
            ], $this->toPublicPayload($snapshot));
        })->values();

        return response()->json(['success' => true, 'data' => $data]);
    }

    protected function toPublicPayload(StudentTermFeeClearance $snapshot): array
    {
        return [
            'status' => $snapshot->status,
            'computed_at' => $snapshot->computed_at?->toIso8601String(),
            'final_clearance_deadline' => $snapshot->final_clearance_deadline?->toDateString(),
        ];
    }
}

