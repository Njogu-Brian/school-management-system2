<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentTransportEvent;
use App\Models\StudentTransportStop;
use App\Models\Trip;
use App\Models\TripRun;
use App\Models\User;
use App\Services\TransportAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Per-child morning pickup / evening drop-off coordinates (saved pins + event history).
 */
class ApiStudentTransportStopController extends Controller
{
    private const ADMIN_ROLES = [
        'Super Admin', 'Director', 'Admin', 'Secretary', 'Academic Administrator',
        'Transport Manager', 'transport manager', 'super admin', 'admin',
    ];

    public function __construct(private TransportAssignmentService $assignmentService) {}

    /**
     * Stops for every student on a trip (driver / transport admin).
     * Defaults kind from the trip direction (morning pickup vs evening dropoff).
     */
    public function forTrip(Request $request, int $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        [$allowed, $error] = $this->authorizeTripAccess($request, $model);
        if (! $allowed) {
            return $error;
        }

        $request->validate([
            'date' => ['sometimes', 'date'],
            'kind' => ['sometimes', 'in:morning_pickup,evening_dropoff'],
        ]);

        $date = Carbon::parse($request->input('date', now()->toDateString()));
        $kind = $request->input('kind') ?: StudentTransportStop::kindForTrip($model);

        $students = $this->assignmentService->getStudentsForTrip($model, $date);
        $studentIds = $students->pluck('id')->all();

        $stops = StudentTransportStop::whereIn('student_id', $studentIds)
            ->where('kind', $kind)
            ->get()
            ->keyBy('student_id');

        $todayEvents = StudentTransportEvent::whereIn('student_id', $studentIds)
            ->where('kind', $kind)
            ->whereDate('recorded_at', $date->toDateString())
            ->orderByDesc('recorded_at')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

        $rows = $students->map(function ($s) use ($stops, $todayEvents, $kind) {
            $stop = $stops->get($s->id);
            $event = $todayEvents->get($s->id);

            return [
                'student_id' => $s->id,
                'full_name' => $s->full_name,
                'admission_number' => $s->admission_number,
                'kind' => $kind,
                'latitude' => $stop?->latitude,
                'longitude' => $stop?->longitude,
                'updated_at' => $stop?->updated_at?->toIso8601String(),
                'marked_today' => $event !== null,
                'last_marked_at' => $event?->recorded_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $model->id,
                'date' => $date->toDateString(),
                'kind' => $kind,
                'stops' => $rows,
            ],
        ]);
    }

    /**
     * Saved stop(s) for one student (parent sees own child; admin/driver as authorized).
     */
    public function forStudent(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();
        $student = Student::findOrFail($studentId);

        if (! $this->canViewStudent($user, $student)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view this student\'s transport stops.',
            ], 403);
        }

        $request->validate([
            'kind' => ['sometimes', 'in:morning_pickup,evening_dropoff'],
        ]);

        $query = StudentTransportStop::where('student_id', $student->id);
        if ($request->filled('kind')) {
            $query->where('kind', $request->input('kind'));
        }

        $stops = $query->get()->map(fn (StudentTransportStop $s) => $this->formatStop($s))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'student_id' => $student->id,
                'stops' => $stops,
            ],
        ]);
    }

    /**
     * Mark pickup/drop-off: writes an event and upserts the saved pin.
     */
    public function mark(Request $request, int $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        [$allowed, $error] = $this->authorizeTripAccess($request, $model);
        if (! $allowed) {
            return $error;
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'kind' => ['sometimes', 'in:morning_pickup,evening_dropoff'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'date' => ['sometimes', 'date'],
            'recorded_at' => ['sometimes', 'date'],
        ]);

        $date = Carbon::parse($request->input('date', now()->toDateString()));
        $kind = $validated['kind'] ?? StudentTransportStop::kindForTrip($model);

        $rosterIds = $this->assignmentService->getStudentsForTrip($model, $date)->pluck('id')->all();
        if (! in_array((int) $validated['student_id'], $rosterIds, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not on this trip roster for the given date.',
            ], 422);
        }

        $run = TripRun::where('trip_id', $model->id)
            ->whereDate('run_date', $date->toDateString())
            ->first();

        $recordedAt = $request->filled('recorded_at')
            ? Carbon::parse($request->input('recorded_at'))
            : now();

        $userId = $request->user()->id;

        [$stop, $event] = DB::transaction(function () use ($validated, $kind, $model, $run, $recordedAt, $userId) {
            $stop = StudentTransportStop::updateOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'kind' => $kind,
                ],
                [
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                ]
            );

            $event = StudentTransportEvent::create([
                'student_id' => $validated['student_id'],
                'trip_run_id' => $run?->id,
                'trip_id' => $model->id,
                'kind' => $kind,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'recorded_at' => $recordedAt,
                'marked_by' => $userId,
            ]);

            return [$stop, $event];
        });

        return response()->json([
            'success' => true,
            'message' => $kind === StudentTransportStop::KIND_MORNING_PICKUP
                ? 'Morning pickup point saved.'
                : 'Evening drop-off point saved.',
            'data' => [
                'stop' => $this->formatStop($stop),
                'event' => [
                    'id' => $event->id,
                    'student_id' => $event->student_id,
                    'kind' => $event->kind,
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                    'recorded_at' => $event->recorded_at?->toIso8601String(),
                    'trip_run_id' => $event->trip_run_id,
                    'trip_id' => $event->trip_id,
                ],
            ],
        ]);
    }

    private function formatStop(StudentTransportStop $stop): array
    {
        return [
            'id' => $stop->id,
            'student_id' => $stop->student_id,
            'kind' => $stop->kind,
            'latitude' => $stop->latitude,
            'longitude' => $stop->longitude,
            'updated_at' => $stop->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{0: bool, 1: ?JsonResponse}
     */
    private function authorizeTripAccess(Request $request, Trip $trip): array
    {
        $user = $request->user();
        if (! $user) {
            return [false, response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401)];
        }

        if ($this->isTransportAdmin($user)) {
            return [true, null];
        }

        $staff = $user->staff;
        if ($staff && (int) $trip->driver_id === (int) $staff->id) {
            return [true, null];
        }

        return [false, response()->json([
            'success' => false,
            'message' => 'Not allowed to manage stops for this trip.',
        ], 403)];
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
