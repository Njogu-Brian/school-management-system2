<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAssignment;
use Illuminate\Http\Request;

/**
 * Permanent student ↔ trip transport assignments (StudentAssignment).
 */
class ApiStudentAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $query = StudentAssignment::with([
            'student.classroom',
            'student.stream',
            'morningTrip.vehicle',
            'eveningTrip.vehicle',
            'morningDropOffPoint',
            'eveningDropOffPoint',
        ])->orderByDesc('id');

        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->student_id);
        }
        if ($request->filled('trip_id')) {
            $tripId = (int) $request->trip_id;
            $query->where(function ($q) use ($tripId) {
                $q->where('morning_trip_id', $tripId)
                    ->orWhere('evening_trip_id', $tripId);
            });
        }

        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (StudentAssignment $a) => $this->serialize($a))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $assignment = StudentAssignment::with([
            'student.classroom',
            'student.stream',
            'morningTrip.vehicle',
            'eveningTrip.vehicle',
            'morningDropOffPoint',
            'eveningDropOffPoint',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($assignment),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'morning_trip_id' => 'nullable|exists:trips,id',
            'evening_trip_id' => 'nullable|exists:trips,id',
        ]);

        if (empty($validated['morning_trip_id']) && empty($validated['evening_trip_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Select at least a morning or evening trip.',
            ], 422);
        }

        $exists = StudentAssignment::where('student_id', $validated['student_id'])->first();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Student already has a permanent transport assignment. Update it instead.',
                'data' => $this->serialize($exists->load([
                    'student.classroom',
                    'morningTrip',
                    'eveningTrip',
                ])),
            ], 422);
        }

        $student = Student::findOrFail($validated['student_id']);
        $dropOffPointId = $student->drop_off_point_id;

        $assignment = StudentAssignment::create([
            'student_id' => $validated['student_id'],
            'morning_trip_id' => $validated['morning_trip_id'] ?? null,
            'evening_trip_id' => $validated['evening_trip_id'] ?? null,
            'morning_drop_off_point_id' => $dropOffPointId,
            'evening_drop_off_point_id' => $dropOffPointId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student permanently assigned to trip(s).',
            'data' => $this->serialize($assignment->load([
                'student.classroom',
                'student.stream',
                'morningTrip.vehicle',
                'eveningTrip.vehicle',
                'morningDropOffPoint',
                'eveningDropOffPoint',
            ])),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $assignment = StudentAssignment::findOrFail($id);

        $validated = $request->validate([
            'morning_trip_id' => 'nullable|exists:trips,id',
            'evening_trip_id' => 'nullable|exists:trips,id',
        ]);

        $student = $assignment->student ?? Student::find($assignment->student_id);
        $dropOffPointId = $student?->drop_off_point_id;

        $assignment->update([
            'morning_trip_id' => array_key_exists('morning_trip_id', $validated)
                ? $validated['morning_trip_id']
                : $assignment->morning_trip_id,
            'evening_trip_id' => array_key_exists('evening_trip_id', $validated)
                ? $validated['evening_trip_id']
                : $assignment->evening_trip_id,
            'morning_drop_off_point_id' => $dropOffPointId,
            'evening_drop_off_point_id' => $dropOffPointId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Permanent assignment updated.',
            'data' => $this->serialize($assignment->fresh([
                'student.classroom',
                'student.stream',
                'morningTrip.vehicle',
                'eveningTrip.vehicle',
                'morningDropOffPoint',
                'eveningDropOffPoint',
            ])),
        ]);
    }

    public function destroy(int $id)
    {
        $assignment = StudentAssignment::findOrFail($id);
        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permanent assignment removed.',
        ]);
    }

    /**
     * Assign or transfer a student permanently onto a trip (upsert).
     */
    public function assignToTrip(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'trip_id' => 'required|exists:trips,id',
            'leg' => 'required|in:morning,evening,both',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $dropOffPointId = $student->drop_off_point_id;
        $tripId = (int) $validated['trip_id'];
        $leg = $validated['leg'];

        $assignment = StudentAssignment::firstOrNew(['student_id' => $student->id]);
        if ($leg === 'morning' || $leg === 'both') {
            $assignment->morning_trip_id = $tripId;
            $assignment->morning_drop_off_point_id = $dropOffPointId;
        }
        if ($leg === 'evening' || $leg === 'both') {
            $assignment->evening_trip_id = $tripId;
            $assignment->evening_drop_off_point_id = $dropOffPointId;
        }
        $assignment->student_id = $student->id;
        $assignment->save();

        return response()->json([
            'success' => true,
            'message' => 'Permanent transport assignment saved.',
            'data' => $this->serialize($assignment->load([
                'student.classroom',
                'student.stream',
                'morningTrip.vehicle',
                'eveningTrip.vehicle',
                'morningDropOffPoint',
                'eveningDropOffPoint',
            ])),
        ]);
    }

    protected function serialize(StudentAssignment $a): array
    {
        return [
            'id' => $a->id,
            'student_id' => $a->student_id,
            'student_name' => $a->student?->full_name,
            'admission_number' => $a->student?->admission_number,
            'class_name' => $a->student?->classroom?->name,
            'stream_name' => $a->student?->stream?->name,
            'morning_trip_id' => $a->morning_trip_id,
            'morning_trip_name' => $a->morningTrip?->trip_name,
            'evening_trip_id' => $a->evening_trip_id,
            'evening_trip_name' => $a->eveningTrip?->trip_name,
            'morning_drop_off_point' => $a->morningDropOffPoint?->name,
            'evening_drop_off_point' => $a->eveningDropOffPoint?->name,
        ];
    }
}
