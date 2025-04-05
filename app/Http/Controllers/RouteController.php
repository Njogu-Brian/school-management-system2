<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Vehicle;
use App\Models\DropOffPoint;
use App\Models\Trip;
class RouteController extends Controller
{

    public function index()
{
    $routes = Route::with('vehicles', 'dropOffPoints')->get();
    $students = Student::all();
    $vehicles = Vehicle::all();
    $trips = Trip::all();
    $dropOffPoints = DropOffPoint::all();

    return view('routes.index', compact('routes', 'students', 'vehicles', 'trips', 'dropOffPoints'));
}

    public function create()
    {
        $vehicles = \App\Models\Vehicle::all();
        return view('routes.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'area' => 'nullable|string',
            'vehicle_ids' => 'nullable|array',
        ]);

        $route = Route::create($request->only('name', 'area'));

        // Attach selected vehicles if any
        if ($request->has('vehicle_ids')) {
            $route->vehicles()->sync($request->vehicle_ids);
        }

        return redirect()->route('routes.index')->with('success', 'Route added and vehicles assigned successfully.');
    }


    public function edit(Route $route)
    {
        $vehicles = \App\Models\Vehicle::all();
        $selectedVehicles = $route->vehicles()->pluck('vehicles.id')->toArray();
        return view('routes.edit', compact('route', 'vehicles', 'selectedVehicles'));
    }


    public function update(Request $request, Route $route)
    {
        $request->validate([
            'name' => 'required|string',
            'area' => 'nullable|string',
            'vehicle_ids' => 'nullable|array',
        ]);

        $route->update($request->only('name', 'area'));

        // Sync vehicles to update assignments
        if ($request->has('vehicle_ids')) {
            $route->vehicles()->sync($request->vehicle_ids);
        } else {
            $route->vehicles()->sync([]); // Clear vehicles if none selected
        }

        return redirect()->route('routes.index')->with('success', 'Route updated successfully.');
    }


    public function destroy(Route $route)
    {
        if ($route->trips()->exists() || $route->assignments()->exists()) {
            return redirect()->route('routes.index')->with('error', 'Cannot delete route with associated trips or assignments.');
        }

        $route->vehicles()->detach(); // Detach vehicles before deleting
        $route->delete();

        return redirect()->route('routes.index')->with('success', 'Route deleted.');
    }

    public function assignVehicle(Request $request, Route $route)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $route->vehicles()->attach($request->vehicle_id);

        return redirect()->route('routes.index')->with('success', 'Vehicle assigned to route successfully.');
    }

}

