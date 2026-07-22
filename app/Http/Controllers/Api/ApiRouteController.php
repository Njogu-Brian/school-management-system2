<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentAssignment;
use App\Models\TransportSpecialAssignment;
use App\Models\Trip;
use App\Services\TransportAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Mobile "routes" list — backed by trips after the legacy `routes` table was removed
 * (see migration remove_routes_from_transport_module).
 */
class ApiRouteController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $query = Trip::with(['vehicle', 'driver', 'stops.dropOffPoint']);

        if ($request->filled('search')) {
            $search = '%' . addcslashes($request->search, '%_\\') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('trip_name', 'like', $search)
                    ->orWhere('direction', 'like', $search)
                    ->orWhereHas('vehicle', function ($v) use ($search) {
                        $v->where('vehicle_number', 'like', $search)
                            ->orWhere('make', 'like', $search)
                            ->orWhere('model', 'like', $search);
                    })
                    ->orWhereHas('driver', function ($d) use ($search) {
                        $d->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search)
                            ->orWhere('staff_id', 'like', $search);
                    });
            });
        }

        $paginated = $query->orderBy('trip_name')->paginate($perPage);

        $data = $paginated->getCollection()->map(fn ($t) => $this->formatTripAsRoute($t))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $data,
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        $trip = Trip::with(['vehicle', 'driver', 'stops.dropOffPoint'])->findOrFail($id);
        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
        $students = app(TransportAssignmentService::class)->getStudentsForTrip($trip, $date);

        $payload = $this->formatTripAsRoute($trip);
        $payload['students_count'] = $students->count();
        $payload['students'] = $students->map(fn ($s) => $this->formatRouteStudent($s, $trip->id))->values()->all();

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function students(Request $request, int $id)
    {
        $trip = Trip::findOrFail($id);
        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();
        $students = app(TransportAssignmentService::class)->getStudentsForTrip($trip, $date);

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $trip->id,
                'date' => $date->toDateString(),
                'students' => $students->map(fn ($s) => $this->formatRouteStudent($s, $trip->id))->values(),
            ],
        ]);
    }

    /**
     * Permanent assignment — updates Student.trip_id and StudentAssignment morning/evening trip.
     * Short-term — TransportSpecialAssignment with start/end (auto-reverts after end_date).
     */
    public function assignStudent(Request $request, int $id)
    {
        $trip = Trip::findOrFail($id);
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'mode' => 'required|in:permanent,short_term',
            'leg' => 'nullable|in:morning,evening,both',
            'start_date' => 'nullable|date|required_if:mode,short_term',
            'end_date' => 'nullable|date|after_or_equal:start_date|required_if:mode,short_term',
            'reason' => 'nullable|string|max:500',
        ]);

        $student = \App\Models\Student::findOrFail($validated['student_id']);
        $leg = $validated['leg'] ?? 'both';

        if ($validated['mode'] === 'permanent') {
            $student->trip_id = $trip->id;
            $student->save();

            $assignment = StudentAssignment::firstOrNew(['student_id' => $student->id]);
            if ($leg === 'morning' || $leg === 'both') {
                $assignment->morning_trip_id = $trip->id;
            }
            if ($leg === 'evening' || $leg === 'both') {
                $assignment->evening_trip_id = $trip->id;
            }
            $assignment->save();

            return response()->json([
                'success' => true,
                'message' => 'Student permanently assigned to this route.',
                'data' => $this->formatRouteStudent($student->fresh(['classroom']), $trip->id),
            ]);
        }

        $special = TransportSpecialAssignment::create([
            'student_id' => $student->id,
            'trip_id' => $trip->id,
            'vehicle_id' => $trip->vehicle_id,
            'assignment_type' => 'temporary',
            'transport_mode' => 'trip',
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? 'Short-term route transfer',
            'status' => 'active',
            'created_by' => $request->user()->id,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Short-term transfer saved. Student reverts after '.$validated['end_date'].'.',
            'data' => [
                'special_assignment_id' => $special->id,
                'student' => $this->formatRouteStudent($student->fresh(['classroom']), $trip->id),
            ],
        ], 201);
    }

    protected function formatRouteStudent($student, int $tripId): array
    {
        $assignment = StudentAssignment::where('student_id', $student->id)->first();
        $special = TransportSpecialAssignment::where('student_id', $student->id)
            ->where('trip_id', $tripId)
            ->where('status', 'active')
            ->where('start_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString());
            })
            ->first();

        $leg = null;
        if ($assignment) {
            if ((int) $assignment->morning_trip_id === $tripId) {
                $leg = 'morning';
            } elseif ((int) $assignment->evening_trip_id === $tripId) {
                $leg = 'evening';
            }
        }

        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'admission_number' => $student->admission_number,
            'class_name' => $student->classroom?->name,
            'assignment_id' => $assignment?->id,
            'leg' => $leg,
            'is_special' => (bool) $special,
            'special_assignment_id' => $special?->id,
            'special_end_date' => $special?->end_date?->toDateString(),
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'name' => 'required|string|max:255',
            'driver_id' => 'nullable|exists:staff,id',
            'direction' => 'nullable|string|max:50',
            'day_of_week' => 'nullable|array',
            'day_of_week.*' => 'integer|in:1,2,3,4,5,6,7',
        ]);

        $data = [
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id' => $validated['driver_id'] ?? null,
            'direction' => $validated['direction'] ?? null,
            'trip_name' => $validated['name'],
            'day_of_week' => ! empty($validated['day_of_week'])
                ? array_map('intval', $validated['day_of_week'])
                : null,
        ];

        $trip = Trip::create($data);
        $trip->load(['vehicle', 'driver', 'stops.dropOffPoint']);

        return response()->json([
            'success' => true,
            'message' => 'Trip created.',
            'data' => $this->formatTripAsRoute($trip),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $trip = Trip::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'name' => 'required|string|max:255',
            'driver_id' => 'nullable|exists:staff,id',
            'direction' => 'nullable|string|max:50',
            'day_of_week' => 'nullable|array',
            'day_of_week.*' => 'integer|in:1,2,3,4,5,6,7',
        ]);

        $trip->update([
            'vehicle_id' => $validated['vehicle_id'],
            'driver_id' => $validated['driver_id'] ?? null,
            'direction' => $validated['direction'] ?? null,
            'trip_name' => $validated['name'],
            'day_of_week' => ! empty($validated['day_of_week'])
                ? array_map('intval', $validated['day_of_week'])
                : null,
        ]);

        $trip->load(['vehicle', 'driver', 'stops.dropOffPoint']);

        return response()->json([
            'success' => true,
            'message' => 'Trip updated.',
            'data' => $this->formatTripAsRoute($trip),
        ]);
    }

    public function destroy(int $id)
    {
        $trip = Trip::findOrFail($id);

        if ($trip->assignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a trip with assigned students.',
            ], 422);
        }

        $trip->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trip deleted.',
        ]);
    }

    protected function formatTripAsRoute(Trip $t): array
    {
        $vehicle = $t->vehicle;
        $driver = $t->driver;

        $stops = $t->stops->map(function ($stop) use ($t) {
            $p = $stop->dropOffPoint;

            return [
                'id' => $stop->id,
                'route_id' => $t->id,
                'name' => $p?->name ?? ('Stop '.$stop->sequence_order),
                'location' => '',
                'sequence' => (int) $stop->sequence_order,
                'pickup_time' => $stop->estimated_time ? $stop->estimated_time->format('H:i') : null,
            ];
        })->values()->all();

        $dayLabel = null;
        if (is_array($t->day_of_week) && count($t->day_of_week) > 0) {
            $dayLabel = 'Days: '.implode(', ', $t->day_of_week);
        }

        $parts = array_filter([$t->direction, $dayLabel]);

        return [
            'id' => $t->id,
            'name' => $t->trip_name ?: ($t->name ?? 'Trip #'.$t->id),
            'code' => null,
            'description' => $parts ? implode(' · ', $parts) : null,
            'vehicle_id' => $t->vehicle_id,
            'vehicle_registration' => $vehicle?->vehicle_number,
            'driver_id' => $t->driver_id,
            'driver_name' => $driver ? $driver->full_name : null,
            'fee_amount' => null,
            'status' => 'active',
            'created_at' => $t->created_at->toIso8601String(),
            'updated_at' => $t->updated_at->toIso8601String(),
            'drop_points' => $stops,
            'students_count' => null,
        ];
    }
}
