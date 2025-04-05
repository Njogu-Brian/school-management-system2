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
        $routes = Route::all();
        $vehicles = Vehicle::all();
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
        $routes = Route::all();
        $vehicles = Vehicle::all();
        return view('trips.edit', compact('trip', 'vehicles', 'routes'));
    }

    public function update(Request $request, Trip $trip)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Morning,Evening',
            'route_id' => 'required|exists:routes,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);
    
        $trip->update($request->all());
    
        return redirect()->route('trips.index')->with('success', 'Trip updated successfully!');
    } 

    public function destroy(Trip $trip)
    {
        if ($trip->assignments()->exists()) {
            return redirect()->route('trips.index')->with('error', 'Cannot delete a trip with assigned students.');
        }
        
        $trip->delete();
        return redirect()->route('trips.index')->with('success', 'Trip deleted successfully.');
    }

}
