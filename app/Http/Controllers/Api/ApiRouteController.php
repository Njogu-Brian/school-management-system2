<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
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

        return response()->json([
            'success' => true,
            'data' => $this->formatTripAsRoute($trip),
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
