<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class ApiVehicleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 30), 100);
        $query = Vehicle::query()->withCount('trips');

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('driver_name', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('vehicle_number')->paginate($perPage);
        $data = $paginated->getCollection()->map(fn (Vehicle $v) => $this->serialize($v))->values();

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

    public function show(int $id)
    {
        $vehicle = Vehicle::withCount('trips')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->serialize($vehicle),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_number' => 'required|string|max:50|unique:vehicles,vehicle_number',
            'driver_name' => 'nullable|string|max:255',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'chassis_number' => 'nullable|string|max:100',
        ]);

        $vehicle = Vehicle::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle created.',
            'data' => $this->serialize($vehicle),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validated = $request->validate([
            'vehicle_number' => 'required|string|max:50|unique:vehicles,vehicle_number,'.$vehicle->id,
            'driver_name' => 'nullable|string|max:255',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'type' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'chassis_number' => 'nullable|string|max:100',
        ]);

        $vehicle->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated.',
            'data' => $this->serialize($vehicle->fresh()),
        ]);
    }

    public function destroy(int $id)
    {
        $vehicle = Vehicle::withCount('trips')->findOrFail($id);

        if ($vehicle->trips_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a vehicle that has trips assigned.',
            ], 422);
        }

        $vehicle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vehicle deleted.',
        ]);
    }

    protected function serialize(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'vehicle_number' => $vehicle->vehicle_number,
            'driver_name' => $vehicle->driver_name,
            'make' => $vehicle->make,
            'model' => $vehicle->model,
            'type' => $vehicle->type,
            'capacity' => $vehicle->capacity,
            'chassis_number' => $vehicle->chassis_number,
            'trips_count' => (int) ($vehicle->trips_count ?? $vehicle->trips()->count()),
            'created_at' => $vehicle->created_at?->toIso8601String(),
            'updated_at' => $vehicle->updated_at?->toIso8601String(),
        ];
    }
}
