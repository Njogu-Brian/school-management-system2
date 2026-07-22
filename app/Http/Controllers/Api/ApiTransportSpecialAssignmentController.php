<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportSpecialAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Short-term / dated transport overrides (TransportSpecialAssignment).
 */
class ApiTransportSpecialAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $query = TransportSpecialAssignment::with([
            'student.classroom',
            'vehicle',
            'trip.vehicle',
            'dropOffPoint',
            'approver',
            'creator',
        ])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->student_id);
        }
        if ($request->filled('trip_id')) {
            $query->where('trip_id', (int) $request->trip_id);
        }

        $paginated = $query->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (TransportSpecialAssignment $a) => $this->serialize($a))->values();

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'trip_id' => 'nullable|exists:trips,id',
            'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'transport_mode' => 'required|in:vehicle,trip,own_means',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
            'activate' => 'sometimes|boolean',
        ]);

        if ($validated['transport_mode'] === 'vehicle' && empty($validated['vehicle_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle is required when transport mode is vehicle.',
            ], 422);
        }
        if ($validated['transport_mode'] === 'trip' && empty($validated['trip_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Trip is required when transport mode is trip.',
            ], 422);
        }

        $activate = $request->boolean('activate', true);

        $assignment = TransportSpecialAssignment::create([
            'student_id' => $validated['student_id'],
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'trip_id' => $validated['trip_id'] ?? null,
            'drop_off_point_id' => $validated['drop_off_point_id'] ?? null,
            'assignment_type' => 'student_specific',
            'transport_mode' => $validated['transport_mode'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'reason' => $validated['reason'] ?? 'Short-term transfer',
            'status' => $activate ? 'active' : 'pending',
            'approved_by' => $activate ? Auth::id() : null,
            'approved_at' => $activate ? now() : null,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $activate
                ? 'Short-term transport assignment activated.'
                : 'Short-term assignment created and pending approval.',
            'data' => $this->serialize($assignment->load([
                'student.classroom',
                'vehicle',
                'trip.vehicle',
                'dropOffPoint',
            ])),
        ], 201);
    }

    public function approve(int $id)
    {
        $assignment = TransportSpecialAssignment::findOrFail($id);
        if ($assignment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This assignment has already been processed.',
            ], 422);
        }

        $assignment->update([
            'status' => 'active',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Special assignment approved.',
            'data' => $this->serialize($assignment->fresh(['student', 'trip', 'vehicle'])),
        ]);
    }

    public function cancel(int $id)
    {
        $assignment = TransportSpecialAssignment::findOrFail($id);
        if (! in_array($assignment->status, ['active', 'pending'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'This assignment cannot be cancelled.',
            ], 422);
        }

        $assignment->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Special assignment cancelled.',
            'data' => $this->serialize($assignment->fresh(['student', 'trip', 'vehicle'])),
        ]);
    }

    protected function serialize(TransportSpecialAssignment $a): array
    {
        return [
            'id' => $a->id,
            'student_id' => $a->student_id,
            'student_name' => $a->student?->full_name,
            'admission_number' => $a->student?->admission_number,
            'class_name' => $a->student?->classroom?->name,
            'vehicle_id' => $a->vehicle_id,
            'vehicle_number' => $a->vehicle?->vehicle_number,
            'trip_id' => $a->trip_id,
            'trip_name' => $a->trip?->trip_name,
            'drop_off_point' => $a->dropOffPoint?->name,
            'assignment_type' => $a->assignment_type,
            'transport_mode' => $a->transport_mode,
            'start_date' => $a->start_date?->toDateString(),
            'end_date' => $a->end_date?->toDateString(),
            'reason' => $a->reason,
            'status' => $a->status,
        ];
    }
}
