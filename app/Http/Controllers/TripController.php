<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Route;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index()
    {
        $trips = Trip::with(['vehicle', 'route'])->get();
        return view('trips.index', compact('trips'));
    }

    public function create()
    {
        $vehicles = Vehicle::all();
        $routes = Route::all();
        return view('trips.create', compact('vehicles', 'routes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'trip_name' => 'required|string|max:255',
        ]);

        Trip::create($request->all());
        return redirect()->route('trips.index')->with('success', 'Trip created successfully.');
    }

    public function edit(Trip $trip)
    {
        $vehicles = Vehicle::all();
        $routes = Route::all();
        return view('trips.edit', compact('trip', 'vehicles', 'routes'));
    }

    public function update(Request $request, Trip $trip)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'trip_name' => 'required|string|max:255',
        ]);

        $trip->update($request->all());
        return redirect()->route('trips.index')->with('success', 'Trip updated successfully.');
    }

    public function destroy(Trip $trip)
    {
        $trip->delete();
        return redirect()->route('trips.index')->with('success', 'Trip deleted successfully.');
    }
}
