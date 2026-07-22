<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Trip;
use App\Models\TripRun;
use App\Models\User;
use App\Services\TransportAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Live GPS tracking for parents/admins: resolves a student's (or the whole fleet's)
 * currently in-progress {@see TripRun} and reports the bus's last known position.
 */
class ApiTransportTrackingController extends Controller
{
    private const ADMIN_ROLES = [
        'Super Admin', 'Director', 'Admin', 'Secretary', 'Academic Administrator',
        'Transport Manager', 'transport manager', 'super admin', 'admin',
    ];

    public function __construct(private TransportAssignmentService $assignmentService) {}

    /**
     * Live bus location for a single student's current (morning/evening) trip, if any.
     */
    public function liveForStudent(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();
        $student = Student::with(['classroom'])->findOrFail($studentId);

        if (! $this->canViewStudent($user, $student)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view this student\'s transport.',
            ], 403);
        }

        $date = Carbon::today();

        $direction = $request->input('direction');
        if (! in_array($direction, ['pickup', 'dropoff'], true)) {
            $direction = now()->hour < 12 ? 'pickup' : 'dropoff';
        }

        $assignment = $this->assignmentService->getStudentAssignment($student, $date, $direction);

        if (! $assignment) {
            return response()->json([
                'success' => true,
                'data' => [
                    'live' => false,
                    'direction' => $direction,
                    'message' => 'No transport assignment found for today.',
                ],
            ]);
        }

        if (($assignment['type'] ?? null) === 'own_means') {
            return response()->json([
                'success' => true,
                'data' => [
                    'live' => false,
                    'direction' => $direction,
                    'message' => 'Student is using their own means of transport today.',
                ],
            ]);
        }

        $trip = $assignment['trip'] ?? null;
        if (! $trip && ! empty($assignment['trip_id'])) {
            $trip = Trip::with(['vehicle', 'driver'])->find($assignment['trip_id']);
        }
        if (! $trip && ! empty($assignment['vehicle_id'])) {
            $trip = Trip::with(['vehicle', 'driver'])
                ->where('vehicle_id', $assignment['vehicle_id'])
                ->whereNotNull('driver_id')
                ->first();
        }

        if (! $trip) {
            return response()->json([
                'success' => true,
                'data' => [
                    'live' => false,
                    'direction' => $direction,
                    'message' => 'No trip is configured for today\'s transport assignment.',
                ],
            ]);
        }

        $run = TripRun::where('trip_id', $trip->id)
            ->whereDate('run_date', $date->toDateString())
            ->where('status', 'in_progress')
            ->first();

        if (! $run) {
            return response()->json([
                'success' => true,
                'data' => [
                    'live' => false,
                    'direction' => $direction,
                    'trip_id' => $trip->id,
                    'trip_name' => $trip->trip_name,
                    'message' => 'The trip has not started yet.',
                ],
            ]);
        }

        $freshness = $run->last_location_at ? now()->diffInSeconds($run->last_location_at) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'live' => $run->isLive(),
                'direction' => $direction,
                'trip_id' => $trip->id,
                'trip_name' => $trip->trip_name,
                'run_id' => $run->id,
                'status' => $run->status,
                'started_at' => $run->started_at?->toIso8601String(),
                'vehicle' => $trip->vehicle ? [
                    'id' => $trip->vehicle->id,
                    'vehicle_number' => $trip->vehicle->vehicle_number,
                    'type' => $trip->vehicle->type,
                ] : null,
                'driver_name' => $trip->driver?->full_name,
                'latitude' => $run->last_latitude,
                'longitude' => $run->last_longitude,
                'accuracy_meters' => $run->last_accuracy_meters,
                'speed_kmh' => $run->last_speed_kmh,
                'last_location_at' => $run->last_location_at?->toIso8601String(),
                'freshness_seconds' => $freshness,
            ],
        ]);
    }

    /**
     * Admin-only: every in-progress trip run right now, with last known position.
     */
    public function liveFleet(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $this->isTransportAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Not allowed.',
            ], 403);
        }

        $runs = TripRun::with(['trip', 'driver', 'vehicle'])
            ->where('status', 'in_progress')
            ->whereDate('run_date', Carbon::today()->toDateString())
            ->get();

        $data = $runs->map(function (TripRun $run) {
            $studentCount = $run->trip
                ? $this->assignmentService->getStudentsForTrip($run->trip, Carbon::parse($run->run_date))->count()
                : 0;
            $freshness = $run->last_location_at ? now()->diffInSeconds($run->last_location_at) : null;

            return [
                'run_id' => $run->id,
                'trip_id' => $run->trip_id,
                'trip_name' => $run->trip?->trip_name,
                'direction' => $run->trip?->direction,
                'driver_id' => $run->driver_id,
                'driver_name' => $run->driver?->full_name,
                'vehicle_id' => $run->vehicle_id,
                'vehicle_number' => $run->vehicle?->vehicle_number,
                'status' => $run->status,
                'started_at' => $run->started_at?->toIso8601String(),
                'latitude' => $run->last_latitude,
                'longitude' => $run->last_longitude,
                'last_location_at' => $run->last_location_at?->toIso8601String(),
                'freshness_seconds' => $freshness,
                'student_count' => $studentCount,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => Carbon::today()->toDateString(),
                'runs' => $data,
                'total' => $data->count(),
            ],
        ]);
    }

    /**
     * Admin or the assigned driver: the current run for a trip on a given (default today) date.
     */
    public function activeRunForTrip(Request $request, int $trip): JsonResponse
    {
        $user = $request->user();
        $model = Trip::with(['vehicle', 'driver'])->findOrFail($trip);

        $staff = $user?->staff;
        $isAssignedDriver = $staff && (int) $model->driver_id === (int) $staff->id;

        if (! $isAssignedDriver && ! $this->isTransportAdmin($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Not allowed.',
            ], 403);
        }

        $request->validate(['date' => ['sometimes', 'date']]);
        $date = Carbon::parse($request->input('date', now()->toDateString()));

        $run = TripRun::where('trip_id', $model->id)
            ->whereDate('run_date', $date->toDateString())
            ->first();

        $freshness = $run?->last_location_at ? now()->diffInSeconds($run->last_location_at) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $model->id,
                'trip_name' => $model->trip_name,
                'date' => $date->toDateString(),
                'vehicle' => $model->vehicle ? [
                    'id' => $model->vehicle->id,
                    'vehicle_number' => $model->vehicle->vehicle_number,
                ] : null,
                'driver_name' => $model->driver?->full_name,
                'run' => $run ? [
                    'id' => $run->id,
                    'status' => $run->status,
                    'started_at' => $run->started_at?->toIso8601String(),
                    'ended_at' => $run->ended_at?->toIso8601String(),
                    'latitude' => $run->last_latitude,
                    'longitude' => $run->last_longitude,
                    'last_location_at' => $run->last_location_at?->toIso8601String(),
                    'freshness_seconds' => $freshness,
                ] : null,
            ],
        ]);
    }

    private function canViewStudent(?User $user, Student $student): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->canAccessStudent($student->id)) {
            return true;
        }

        return $this->isTransportAdmin($user);
    }

    private function isTransportAdmin(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(self::ADMIN_ROLES);
    }
}
