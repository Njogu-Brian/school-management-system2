<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentTermFeeClearance;
use App\Models\Term;
use App\Models\Trip;
use App\Models\StudentAssignment;
use App\Services\FeeClearanceStatusService;
use Carbon\Carbon;
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
            'meta' => $this->enforcementMeta($term),
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

        return response()->json([
            'success' => true,
            'meta' => $this->enforcementMeta($term),
            'data' => $data,
        ]);
    }

    /**
     * Fee clearance roster for a transport trip (for drivers mobile).
     * Returns status only (no fee amounts).
     */
    public function tripRoster(Request $request, int $id)
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

        $trip = Trip::findOrFail($id);

        $user = $request->user();
        if ($user && $user->hasAnyRole(['Driver', 'driver'])) {
            $staff = $user->staff;
            if (!$staff || (int) $trip->driver_id !== (int) $staff->id) {
                return response()->json(['success' => false, 'message' => 'You are not assigned to this trip.'], 403);
            }
        }

        $direction = strtolower((string) ($trip->direction ?? ''));
        $isMorning = str_contains($direction, 'morning');
        $isEvening = str_contains($direction, 'evening');

        $assignments = StudentAssignment::query()
            ->with(['student', 'morningDropOffPoint', 'eveningDropOffPoint'])
            ->where(function ($q) use ($trip) {
                $q->where('morning_trip_id', $trip->id)
                    ->orWhere('evening_trip_id', $trip->id);
            })
            ->get()
            ->filter(fn ($a) => $a->student && ! $a->student->archive && ! $a->student->is_alumni)
            ->values();

        $studentIds = $assignments->pluck('student_id')->unique()->values();

        $snapshots = StudentTermFeeClearance::where('term_id', $term->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $data = $assignments->map(function ($a) use ($trip, $term, $snapshots, $isMorning, $isEvening) {
            $student = $a->student;
            $snapshot = $snapshots->get($student->id);
            if (! $snapshot) {
                $snapshot = $this->service->upsertSnapshot($student, $term);
            }

            $stop = null;
            if ($isMorning) {
                $stop = $a->morningDropOffPoint?->name;
            } elseif ($isEvening) {
                $stop = $a->eveningDropOffPoint?->name;
            } else {
                $stop = $a->morningDropOffPoint?->name ?? $a->eveningDropOffPoint?->name;
            }

            return [
                'trip_id' => (int) $trip->id,
                'student_id' => (int) $student->id,
                'name' => $student->full_name ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')),
                'admission_number' => $student->admission_number ?? null,
                'stop_name' => $stop,
                ...$this->toPublicPayload($snapshot),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'meta' => $this->enforcementMeta($term),
            'data' => $data,
        ]);
    }

    protected function toPublicPayload(StudentTermFeeClearance $snapshot): array
    {
        return [
            'status' => $snapshot->status,
            'computed_at' => $snapshot->computed_at?->toIso8601String(),
            'final_clearance_deadline' => $snapshot->displayFinalClearanceDeadline()?->toDateString(),
        ];
    }

    protected function enforcementMeta(Term $term): array
    {
        $today = Carbon::now()->startOfDay();
        $day1 = ($term->fee_clearance_day1_date ?: $term->opening_date)?->copy()->startOfDay();
        $strictFrom = ($term->fee_clearance_strict_from_date ?: ($day1 ? $day1->copy()->addDay() : null))?->copy()->startOfDay();

        $level = 'unknown';
        if ($day1) {
            if ($today->lt($day1)) {
                $level = 'preterm';
            } elseif ($today->equalTo($day1)) {
                $level = 'day1';
            } elseif ($strictFrom && $today->gte($strictFrom)) {
                $level = 'strict';
            } else {
                $level = 'grace';
            }
        }

        return [
            'enforcement_level' => $level,
            'day1_date' => $day1?->toDateString(),
            'strict_from_date' => $strictFrom?->toDateString(),
            'term_id' => (int) $term->id,
        ];
    }
}

