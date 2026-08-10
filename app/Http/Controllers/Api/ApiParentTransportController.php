<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DropOffPoint;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Http\Request;

/**
 * Parent-facing transport catalogs for viewing / requesting assignment changes.
 */
class ApiParentTransportController extends Controller
{
    public function options(Request $request)
    {
        $user = $request->user();
        if (! $user || (! $user->parent_id && ! $user->hasAnyRole(['Parent', 'Guardian']))) {
            // Dual-identity staff who can access children as parents.
            if (! $user || ! method_exists($user, 'accessibleStudentIds') || $user->accessibleStudentIds() === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account is not linked to a parent record.',
                ], 403);
            }
        }

        $trips = Trip::query()
            ->with('vehicle:id,vehicle_number,driver_name')
            ->orderBy('trip_name')
            ->get(['id', 'trip_name', 'direction', 'type', 'vehicle_id'])
            ->map(fn (Trip $t) => [
                'id' => $t->id,
                'name' => $t->trip_name,
                'direction' => $t->direction,
                'type' => $t->type,
                'vehicle_id' => $t->vehicle_id,
                'vehicle_number' => $t->vehicle?->vehicle_number,
                'label' => trim(($t->trip_name ?? 'Trip').($t->vehicle?->vehicle_number ? ' · '.$t->vehicle->vehicle_number : '')),
            ])
            ->values();

        $dropOffPoints = DropOffPoint::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (DropOffPoint $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'label' => $p->name,
            ])
            ->values();

        $vehicles = Vehicle::query()
            ->orderBy('vehicle_number')
            ->get(['id', 'vehicle_number', 'driver_name', 'capacity'])
            ->map(fn (Vehicle $v) => [
                'id' => $v->id,
                'vehicle_number' => $v->vehicle_number,
                'driver_name' => $v->driver_name,
                'capacity' => $v->capacity,
                'label' => trim(($v->vehicle_number ?? 'Vehicle').($v->driver_name ? ' · '.$v->driver_name : '')),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'trips' => $trips,
                'drop_off_points' => $dropOffPoints,
                'vehicles' => $vehicles,
            ],
        ]);
    }
}
