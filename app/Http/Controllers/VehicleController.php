<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::all();
        return view('vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        return view('vehicles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_number' => 'required|unique:vehicles',
            'driver_name' => 'required|string',
        ]);

        Vehicle::create($request->all());
        return redirect()->route('vehicles.index')->with('success', 'Vehicle added.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'vehicle_number' => 'required|unique:vehicles,vehicle_number,' . $vehicle->id,
            'driver_name' => 'required|string',
        ]);

        $vehicle->update($request->all());
        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted.');
    }
}
