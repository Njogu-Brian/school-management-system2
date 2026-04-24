<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentDailyPickup;
use App\Models\Trip;
use App\Models\TransportSpecialAssignment;
use App\Models\Vehicle;
use App\Services\TransportAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teacher-facing transport: read-only list of assigned students' transport
 * plus ability to record daily pickups (parent collected the child) and
 * temporary same-day vehicle/trip changes.
 */
class ApiTeacherTransportController extends Controller
{
    public function __construct(private TransportAssignmentService $service) {}

    public function students(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->canUseApi($user)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

        $query = Student::with(['classroom', 'stream'])
            ->where('archive', 0)
            ->where('is_alumni', false);

        if ($this->isTeacherOnly($user)) {
            $user->applyTeacherStudentFilter($query);
        } elseif ($request->filled('classroom_id')) {
            $query->where('classroom_id', (int) $request->classroom_id);
        }

        $students = $query->orderBy('first_name')->limit(200)->get();

        $data = $students->map(function (Student $s) use ($date) {
            $morning = $this->service->getStudentAssignment($s, $date, 'pickup');
            $evening = $this->service->getStudentAssignment($s, $date, 'dropoff');
            $pickup = StudentDailyPickup::where('student_id', $s->id)
                ->whereDate('date', $date->toDateString())
                ->first();
            return [
                'id' => $s->id,
                'full_name' => $s->full_name,
                'admission_number' => $s->admission_number,
                'class_name' => $s->classroom?->name,
                'stream_name' => $s->stream?->name,
                'morning' => $this->formatLeg($morning),
                'evening' => $this->formatLeg($evening),
                'pickup' => $pickup ? [
                    'id' => $pickup->id,
                    'direction' => $pickup->direction,
                    'picked_up_by' => $pickup->picked_up_by,
                    'skip_evening_trip' => (bool) $pickup->skip_evening_trip,
                    'notes' => $pickup->notes,
                    'recorded_at' => $pickup->created_at?->toIso8601String(),
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->toDateString(),
                'students' => $data,
            ],
        ]);
    }

    public function markCollectedByParent(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->canUseApi($user)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'date' => ['nullable', 'date'],
            'direction' => ['nullable', 'in:morning,evening,both'],
            'picked_up_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if (! $this->canTeacherTouchStudent($user, $student)) {
            return response()->json(['success' => false, 'message' => 'Not assigned to this student.'], 403);
        }

        $date = Carbon::parse($validated['date'] ?? Carbon::today()->toDateString());
        $direction = $validated['direction'] ?? 'evening';

        $pickup = DB::transaction(function () use ($student, $user, $validated, $date, $direction) {
            $special = TransportSpecialAssignment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'start_date' => $date->toDateString(),
                    'end_date' => $date->toDateString(),
                    'transport_mode' => 'own_means',
                ],
                [
                    'assignment_type' => 'temporary',
                    'reason' => $validated['notes'] ?? 'Picked up by parent',
                    'status' => 'active',
                    'created_by' => $user->id,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]
            );

            return StudentDailyPickup::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'date' => $date->toDateString(),
                    'direction' => $direction,
                ],
                [
                    'recorded_by_user_id' => $user->id,
                    'picked_up_by' => $validated['picked_up_by'] ?? 'Parent',
                    'skip_evening_trip' => in_array($direction, ['evening', 'both'], true),
                    'notes' => $validated['notes'] ?? null,
                    'transport_special_assignment_id' => $special->id,
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Pickup recorded.',
            'data' => $pickup->fresh(),
        ]);
    }

    public function cancelPickup(Request $request, int $pickupId)
    {
        $user = $request->user();
        if (! $user || ! $this->canUseApi($user)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $pickup = StudentDailyPickup::findOrFail($pickupId);

        if (! $this->canTeacherTouchStudent($user, $pickup->student)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        DB::transaction(function () use ($pickup) {
            if ($pickup->transport_special_assignment_id) {
                TransportSpecialAssignment::where('id', $pickup->transport_special_assignment_id)
                    ->update(['status' => 'cancelled']);
            }
            $pickup->delete();
        });

        return response()->json(['success' => true, 'message' => 'Pickup cancelled.']);
    }

    public function temporaryReassignment(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->canUseApi($user)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'mode' => ['required', 'in:vehicle,trip'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'trip_id' => ['nullable', 'integer', 'exists:trips,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if (! $this->canTeacherTouchStudent($user, $student)) {
            return response()->json(['success' => false, 'message' => 'Not assigned to this student.'], 403);
        }

        if ($validated['mode'] === 'vehicle' && empty($validated['vehicle_id'])) {
            return response()->json(['success' => false, 'message' => 'Please select a vehicle.'], 422);
        }
        if ($validated['mode'] === 'trip' && empty($validated['trip_id'])) {
            return response()->json(['success' => false, 'message' => 'Please select a trip.'], 422);
        }

        $special = TransportSpecialAssignment::create([
            'student_id' => $student->id,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'trip_id' => $validated['trip_id'] ?? null,
            'assignment_type' => 'temporary',
            'transport_mode' => $validated['mode'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? $validated['start_date'],
            'reason' => $validated['reason'] ?? 'Temporary change by teacher',
            'status' => 'active',
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Temporary change saved.',
            'data' => $special,
        ]);
    }

    public function vehicles(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $this->canUseApi($user)) {
            return response()->json(['success' => false, 'message' => 'Not allowed.'], 403);
        }

        $vehicles = Vehicle::orderBy('vehicle_number')
            ->get(['id', 'vehicle_number', 'driver_name', 'capacity']);

        $trips = Trip::with('vehicle:id,vehicle_number,driver_name')
            ->orderBy('departure_time')
            ->get(['id', 'name', 'direction', 'departure_time', 'vehicle_id']);

        return response()->json([
            'success' => true,
            'data' => ['vehicles' => $vehicles, 'trips' => $trips],
        ]);
    }

    private function formatLeg(?array $leg): ?array
    {
        if (! $leg) return null;

        if (($leg['type'] ?? null) === 'own_means') {
            return [
                'type' => 'own_means',
                'reason' => $leg['reason'] ?? null,
            ];
        }

        $trip = $leg['trip'] ?? null;
        $vehicle = $leg['vehicle'] ?? ($trip?->vehicle ?? null);

        return [
            'type' => $leg['type'] ?? 'trip',
            'trip_id' => $leg['trip_id'] ?? null,
            'trip_name' => $trip?->name,
            'direction' => $trip?->direction,
            'departure_time' => $trip?->departure_time,
            'vehicle_id' => $vehicle?->id,
            'vehicle_registration' => $vehicle?->vehicle_number,
            'vehicle_name' => $vehicle?->driver_name,
            'drop_off_point' => $leg['drop_off_point']?->name ?? null,
        ];
    }

    private function canUseApi($user): bool
    {
        return $user->hasAnyRole([
            'Admin', 'Super Admin', 'admin', 'super admin',
            'Teacher', 'teacher', 'Senior Teacher', 'senior teacher', 'Supervisor', 'supervisor',
        ]);
    }

    private function isTeacherOnly($user): bool
    {
        return $user->hasTeacherLikeRole() && ! $user->hasAnyRole(['Admin', 'Super Admin', 'admin', 'super admin']);
    }

    private function canTeacherTouchStudent($user, Student $student): bool
    {
        if (! $this->isTeacherOnly($user)) {
            return true;
        }
        return $user->canTeacherAccessClassroom((int) $student->classroom_id);
    }
}
