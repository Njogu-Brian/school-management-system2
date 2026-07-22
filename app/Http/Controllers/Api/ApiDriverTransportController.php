<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripAttendance;
use App\Models\TripRun;
use App\Models\TripRunLocation;
use App\Models\Vehicle;
use App\Services\TransportAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mobile driver transport — mirrors web {@see \App\Http\Controllers\Driver\DriverController}
 * using trip templates + roster for a calendar date, plus a per-date "trip run" (start/stop,
 * boarding attendance, live GPS pings) backed by {@see TripRun} and {@see TripRunLocation}.
 */
class ApiDriverTransportController extends Controller
{
    public function __construct(private TransportAssignmentService $assignmentService) {}

    public function index(Request $request)
    {
        $staff = $request->user()?->staff;
        if (! $staff) {
            return response()->json([
                'success' => false,
                'message' => 'A staff profile is required to view assigned trips.',
            ], 403);
        }

        if ($request->filled('driver_id') && (int) $request->driver_id !== (int) $staff->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only list trips for your own driver profile.',
            ], 403);
        }

        $request->validate([
            'date' => ['sometimes', 'date'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $start = $request->has('date')
            ? Carbon::parse($request->input('date'))
            : ($request->filled('date_from')
                ? Carbon::parse($request->input('date_from'))
                : Carbon::today());

        $end = $request->has('date')
            ? $start->copy()
            : ($request->filled('date_to')
                ? Carbon::parse($request->input('date_to'))
                : $start->copy());

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        $daySpan = $start->diffInDays($end) + 1;
        if ($daySpan > 31) {
            return response()->json([
                'success' => false,
                'message' => 'Date range cannot exceed 31 days.',
            ], 422);
        }

        $rows = collect();
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dayAdjusted = $d->dayOfWeek === 0 ? 7 : $d->dayOfWeek;

            $trips = Trip::query()
                ->where('driver_id', $staff->id)
                ->where(function ($q) use ($dayAdjusted) {
                    $q->whereNull('day_of_week')
                        ->orWhereJsonContains('day_of_week', $dayAdjusted);
                })
                ->with(['vehicle', 'driver', 'stops.dropOffPoint'])
                ->orderBy('trip_name')
                ->get();

            foreach ($trips as $trip) {
                $count = $this->assignmentService->getStudentsForTrip($trip, $d->copy())->count();
                $rows->push($this->formatTripRow($trip, $d->copy(), $count));
            }
        }

        $all = $rows->values();
        $total = $all->count();
        $perPage = min((int) $request->input('per_page', 50), 100);
        $page = max(1, (int) $request->input('page', 1));
        $slice = $all->forPage($page, $perPage)->values();
        $lastPage = max(1, (int) ceil($total / $perPage));

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $slice,
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total,
                'from' => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
                'to' => $total > 0 ? min($page * $perPage, $total) : 0,
                'driver_staff_id' => $staff->id,
            ],
        ]);
    }

    public function show(Request $request, int $trip)
    {
        $staff = $request->user()?->staff;
        if (! $staff) {
            return response()->json([
                'success' => false,
                'message' => 'A staff profile is required.',
            ], 403);
        }

        $model = Trip::with(['vehicle', 'driver', 'stops.dropOffPoint'])->findOrFail($trip);

        if ((int) $model->driver_id !== (int) $staff->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not assigned to this trip.',
            ], 403);
        }

        $date = $request->input('date', now()->toDateString());
        $carbon = Carbon::parse($date);
        $students = $this->assignmentService->getStudentsForTrip($model, $carbon);

        $studentRows = $students->map(function ($s) {
            return [
                'id' => $s->id,
                'full_name' => $s->full_name,
                'admission_number' => $s->admission_number,
                'class_name' => $s->classroom?->name,
                'stream_name' => $s->stream?->name,
            ];
        })->values();

        $attendance = TripAttendance::where('trip_id', $model->id)
            ->whereDate('attendance_date', $carbon->toDateString())
            ->get();

        $boardingSummary = [
            'boarded' => $attendance->whereIn('status', ['present', 'late'])->count(),
            'absent' => $attendance->where('status', 'absent')->count(),
            'pending' => max(0, $studentRows->count() - $attendance->count()),
            'total' => $studentRows->count(),
        ];

        $run = $this->findRun($model->id, $carbon);

        return response()->json([
            'success' => true,
            'data' => [
                'trip' => $this->formatTripRow($model, $carbon, $studentRows->count(), $run),
                'students' => $studentRows,
                'student_count' => $studentRows->count(),
                'date' => $carbon->toDateString(),
                'boarding_summary' => $boardingSummary,
                'run' => $this->formatRun($run),
            ],
        ]);
    }

    /**
     * Start (or restart, if previously cancelled) the trip run for a date. Only the
     * driver assigned to the trip may start it, and only one active run per day.
     */
    public function start(Request $request, int $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        [$staff, $error] = $this->authorizeDriverForTrip($request, $model);
        if ($error) {
            return $error;
        }

        $request->validate(['date' => ['sometimes', 'date']]);
        $date = Carbon::parse($request->input('date', now()->toDateString()));

        $run = $this->findRun($model->id, $date);

        if ($run && in_array($run->status, ['in_progress', 'completed'], true)) {
            return response()->json([
                'success' => false,
                'message' => "This trip is already {$run->status} for {$date->toDateString()}.",
            ], 422);
        }

        if ($run) {
            $run->update([
                'status' => 'in_progress',
                'driver_id' => $staff->id,
                'vehicle_id' => $model->vehicle_id,
                'started_at' => now(),
                'ended_at' => null,
                'started_by' => $request->user()->id,
            ]);
        } else {
            $run = TripRun::create([
                'trip_id' => $model->id,
                'run_date' => $date->toDateString(),
                'driver_id' => $staff->id,
                'vehicle_id' => $model->vehicle_id,
                'status' => 'in_progress',
                'started_at' => now(),
                'started_by' => $request->user()->id,
            ]);
        }

        $count = $this->assignmentService->getStudentsForTrip($model, $date)->count();

        return response()->json([
            'success' => true,
            'message' => 'Trip started.',
            'data' => $this->formatTripRow($model, $date, $count, $run),
        ]);
    }

    /**
     * Stop (complete) an in-progress trip run for a date.
     */
    public function stop(Request $request, int $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        [, $error] = $this->authorizeDriverForTrip($request, $model);
        if ($error) {
            return $error;
        }

        $request->validate(['date' => ['sometimes', 'date']]);
        $date = Carbon::parse($request->input('date', now()->toDateString()));

        $run = $this->findRun($model->id, $date);

        if (! $run) {
            return response()->json([
                'success' => false,
                'message' => 'No trip run found for this date.',
            ], 404);
        }

        if ($run->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => "Trip run is not in progress (current status: {$run->status}).",
            ], 422);
        }

        $run->update(['status' => 'completed', 'ended_at' => now()]);

        $count = $this->assignmentService->getStudentsForTrip($model, $date)->count();

        return response()->json([
            'success' => true,
            'message' => 'Trip stopped.',
            'data' => $this->formatTripRow($model, $date, $count, $run),
        ]);
    }

    /**
     * Boarding roster for a date: assigned students merged with their trip attendance status.
     */
    public function boarding(Request $request, int $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        [, $error] = $this->authorizeDriverForTrip($request, $model);
        if ($error) {
            return $error;
        }

        $request->validate(['date' => ['sometimes', 'date']]);
        $date = Carbon::parse($request->input('date', now()->toDateString()));

        $students = $this->assignmentService->getStudentsForTrip($model, $date);

        $attendance = TripAttendance::where('trip_id', $model->id)
            ->whereDate('attendance_date', $date->toDateString())
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function ($s) use ($attendance) {
            $a = $attendance->get($s->id);

            return [
                'student_id' => $s->id,
                'full_name' => $s->full_name,
                'admission_number' => $s->admission_number,
                'class_name' => $s->classroom?->name,
                'stream_name' => $s->stream?->name,
                'status' => $a?->status ?? 'pending',
                'boarded_at' => $a?->boarded_at?->format('H:i'),
                'notes' => $a?->notes,
            ];
        })->values();

        $summary = [
            'boarded' => $rows->whereIn('status', ['present', 'late'])->count(),
            'absent' => $rows->where('status', 'absent')->count(),
            'pending' => $rows->where('status', 'pending')->count(),
            'total' => $rows->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $model->id,
                'date' => $date->toDateString(),
                'direction' => $model->direction,
                'boarded_count' => $summary['boarded'],
                'total_count' => $summary['total'],
                'students' => $rows,
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Mark boarding attendance for one student, or bulk-mark several at once.
     */
    public function markBoarding(Request $request, int $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        [, $error] = $this->authorizeDriverForTrip($request, $model);
        if ($error) {
            return $error;
        }

        $request->validate(['date' => ['sometimes', 'date']]);
        $date = Carbon::parse($request->input('date', now()->toDateString()));

        if ($request->has('attendance')) {
            $validated = $request->validate([
                'attendance' => ['required', 'array', 'min:1'],
                'attendance.*.student_id' => ['required', 'integer', 'exists:students,id'],
                'attendance.*.status' => ['required', 'in:present,absent,late'],
                'attendance.*.boarded_at' => ['nullable', 'date_format:H:i'],
                'attendance.*.notes' => ['nullable', 'string', 'max:500'],
            ]);
            $rows = $validated['attendance'];
        } else {
            $validated = $request->validate([
                'student_id' => ['required', 'integer', 'exists:students,id'],
                'status' => ['required', 'in:present,absent,late'],
                'boarded_at' => ['nullable', 'date_format:H:i'],
                'notes' => ['nullable', 'string', 'max:500'],
            ]);
            $rows = [$validated];
        }

        $rosterIds = $this->assignmentService->getStudentsForTrip($model, $date)->pluck('id')->all();
        $userId = $request->user()->id;

        $saved = DB::transaction(function () use ($rows, $model, $date, $rosterIds, $userId) {
            $result = [];
            foreach ($rows as $row) {
                if (! in_array((int) $row['student_id'], $rosterIds, true)) {
                    continue;
                }

                $result[] = TripAttendance::updateOrCreate(
                    [
                        'trip_id' => $model->id,
                        'student_id' => $row['student_id'],
                        'attendance_date' => $date->toDateString(),
                    ],
                    [
                        'status' => $row['status'],
                        'boarded_at' => $row['boarded_at'] ?? ($row['status'] !== 'absent' ? now()->format('H:i') : null),
                        'notes' => $row['notes'] ?? null,
                        'marked_by' => $userId,
                    ]
                );
            }

            return $result;
        });

        if (empty($saved)) {
            return response()->json([
                'success' => false,
                'message' => 'No matching students found on this trip roster for the given date.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Boarding attendance recorded.',
            'data' => collect($saved)->map(fn (TripAttendance $a) => [
                'student_id' => $a->student_id,
                'status' => $a->status,
                'boarded_at' => $a->boarded_at?->format('H:i'),
                'notes' => $a->notes,
            ])->values(),
        ]);
    }

    /**
     * Record a live GPS ping for the trip's active run.
     */
    public function pingLocation(Request $request, int $trip): JsonResponse
    {
        $model = Trip::findOrFail($trip);
        [, $error] = $this->authorizeDriverForTrip($request, $model);
        if ($error) {
            return $error;
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'numeric', 'min:0'],
            'speed_kmh' => ['nullable', 'numeric', 'min:0'],
            'heading' => ['nullable', 'numeric', 'between:0,360'],
            'date' => ['sometimes', 'date'],
        ]);

        $date = Carbon::parse($request->input('date', now()->toDateString()));
        $run = $this->findRun($model->id, $date);

        if (! $run || $run->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Trip run is not in progress. Start the trip before sending location updates.',
            ], 422);
        }

        $now = now();

        $location = DB::transaction(function () use ($run, $validated, $now) {
            $run->update([
                'last_latitude' => $validated['latitude'],
                'last_longitude' => $validated['longitude'],
                'last_accuracy_meters' => $validated['accuracy_meters'] ?? null,
                'last_speed_kmh' => $validated['speed_kmh'] ?? null,
                'last_location_at' => $now,
            ]);

            return TripRunLocation::create([
                'trip_run_id' => $run->id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy_meters' => $validated['accuracy_meters'] ?? null,
                'speed_kmh' => $validated['speed_kmh'] ?? null,
                'heading' => $validated['heading'] ?? null,
                'recorded_at' => $now,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Location updated.',
            'data' => [
                'trip_run_id' => $run->id,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'recorded_at' => $location->recorded_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Vehicle(s) assigned to the authenticated driver, derived from their trips.
     */
    public function vehicle(Request $request): JsonResponse
    {
        $staff = $request->user()?->staff;
        if (! $staff) {
            return response()->json([
                'success' => false,
                'message' => 'A staff profile is required.',
            ], 403);
        }

        $vehicleIds = Trip::where('driver_id', $staff->id)
            ->whereNotNull('vehicle_id')
            ->pluck('vehicle_id')
            ->unique()
            ->values();

        $vehicles = Vehicle::whereIn('id', $vehicleIds)->orderBy('vehicle_number')->get();

        $mapped = $vehicles->map(fn (Vehicle $v) => [
            'id' => $v->id,
            'vehicle_number' => $v->vehicle_number,
            'driver_name' => $staff->full_name ?? $v->driver_name,
            'make' => $v->make,
            'model' => $v->model,
            'type' => $v->type,
            'capacity' => $v->capacity,
            'status' => $v->status ?? null,
        ])->values();

        return response()->json([
            'success' => true,
            // Mobile clients expect a single primary vehicle object.
            'data' => $mapped->first() ?: [
                'id' => 0,
                'vehicle_number' => null,
                'message' => 'No vehicle assigned to your trips.',
            ],
            'vehicles' => $mapped,
        ]);
    }

    /**
     * Ensure the requesting user has a staff profile matching the trip's driver.
     *
     * @return array{0: ?\App\Models\Staff, 1: ?JsonResponse}
     */
    private function authorizeDriverForTrip(Request $request, Trip $trip): array
    {
        $staff = $request->user()?->staff;
        if (! $staff) {
            return [null, response()->json([
                'success' => false,
                'message' => 'A staff profile is required.',
            ], 403)];
        }

        if ((int) $trip->driver_id !== (int) $staff->id) {
            return [null, response()->json([
                'success' => false,
                'message' => 'You are not assigned to this trip.',
            ], 403)];
        }

        return [$staff, null];
    }

    private function findRun(int $tripId, Carbon $date): ?TripRun
    {
        return TripRun::where('trip_id', $tripId)
            ->whereDate('run_date', $date->toDateString())
            ->first();
    }

    private function formatRun(?TripRun $run): ?array
    {
        if (! $run) {
            return null;
        }

        return [
            'id' => $run->id,
            'status' => $run->status,
            'started_at' => $run->started_at?->toIso8601String(),
            'ended_at' => $run->ended_at?->toIso8601String(),
            'latitude' => $run->last_latitude,
            'longitude' => $run->last_longitude,
            'accuracy_meters' => $run->last_accuracy_meters,
            'speed_kmh' => $run->last_speed_kmh,
            'last_location_at' => $run->last_location_at?->toIso8601String(),
        ];
    }

    protected function formatTripRow(Trip $t, Carbon $date, int $studentCount = 0, ?TripRun $run = null): array
    {
        $vehicle = $t->vehicle;
        $dir = $t->direction ?? 'pickup';
        $run = $run ?? $this->findRun($t->id, $date);

        return [
            'id' => $t->id,
            'route_id' => $t->id,
            'route_name' => $t->trip_name ?: ($t->name ?? 'Trip #'.$t->id),
            'vehicle_registration' => $vehicle?->vehicle_number,
            'vehicle_id' => $t->vehicle_id,
            'driver_id' => $t->driver_id,
            'driver_name' => $t->driver?->full_name,
            'date' => $date->toDateString(),
            'type' => $dir,
            'status' => $run?->status ?? 'scheduled',
            'run_id' => $run?->id,
            'start_time' => $run?->started_at?->toIso8601String(),
            'end_time' => $run?->ended_at?->toIso8601String(),
            'last_latitude' => $run?->last_latitude,
            'last_longitude' => $run?->last_longitude,
            'last_location_at' => $run?->last_location_at?->toIso8601String(),
            'students_on_route' => $studentCount,
            'created_at' => $t->created_at?->toIso8601String() ?? '',
            'updated_at' => $t->updated_at?->toIso8601String() ?? '',
        ];
    }
}
