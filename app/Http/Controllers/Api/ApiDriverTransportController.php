<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\TransportAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Mobile driver transport — mirrors web {@see \App\Http\Controllers\Driver\DriverController}
 * using trip templates + roster for a calendar date (no separate "trip run" table).
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

        return response()->json([
            'success' => true,
            'data' => [
                'trip' => $this->formatTripRow($model, $carbon, $studentRows->count()),
                'students' => $studentRows,
                'student_count' => $studentRows->count(),
                'date' => $carbon->toDateString(),
            ],
        ]);
    }

    protected function formatTripRow(Trip $t, Carbon $date, int $studentCount = 0): array
    {
        $vehicle = $t->vehicle;
        $dir = $t->direction ?? 'pickup';

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
            'status' => 'scheduled',
            'start_time' => null,
            'end_time' => null,
            'students_on_route' => $studentCount,
            'created_at' => $t->created_at?->toIso8601String() ?? '',
            'updated_at' => $t->updated_at?->toIso8601String() ?? '',
        ];
    }
}
