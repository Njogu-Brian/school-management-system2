<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Staff;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index()
    {
        $trips = Trip::with(['vehicle', 'driver.user'])->orderBy('trip_name')->get();
        return view('trips.index', compact('trips'));
    }

    public function create()
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        return view('trips.create', compact('vehicles'));
    }

    

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'name' => 'required|string|max:255',
            'day_of_week' => 'nullable|array',
            'day_of_week.*' => 'integer|in:1,2,3,4,5,6,7',
        ]);

        $data = $request->only(['vehicle_id', 'driver_id', 'direction']);
        $data['trip_name'] = $request->input('name');
        
        // Handle day_of_week array - convert to integers and store as JSON
        $dayOfWeek = $request->input('day_of_week');
        if (is_array($dayOfWeek) && !empty($dayOfWeek)) {
            $data['day_of_week'] = array_map('intval', $dayOfWeek);
        } else {
            $data['day_of_week'] = null;
        }
        
        Trip::create($data);
        return redirect()->route('transport.trips.index')->with('success', 'Trip created successfully.');
    }

    public function edit(Trip $trip)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $trip->load(['vehicle', 'driver.user']);
        return view('trips.edit', compact('trip', 'vehicles'));
    }

    public function update(Request $request, Trip $trip)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vehicle_id' => 'required|exists:vehicles,id',
            'day_of_week' => 'nullable|array',
            'day_of_week.*' => 'integer|in:1,2,3,4,5,6,7',
        ]);
    
        $data = [
            'trip_name' => $request->input('name'),
            'vehicle_id' => $request->input('vehicle_id'),
            'driver_id' => $request->input('driver_id'),
            'direction' => $request->input('direction'),
        ];
        
        // Handle day_of_week array - convert to integers and store as JSON
        $dayOfWeek = $request->input('day_of_week');
        if (is_array($dayOfWeek) && !empty($dayOfWeek)) {
            $data['day_of_week'] = array_map('intval', $dayOfWeek);
        } else {
            $data['day_of_week'] = null;
        }
    
        $trip->update($data);
    
        return redirect()->route('transport.trips.index')->with('success', 'Trip updated successfully!');
    } 

    public function destroy(Trip $trip)
    {
        if ($trip->assignments()->exists()) {
            return redirect()->route('transport.trips.index')->with('error', 'Cannot delete a trip with assigned students.');
        }
        
        $trip->delete();
        return redirect()->route('transport.trips.index')->with('success', 'Trip deleted successfully.');
    }

}
