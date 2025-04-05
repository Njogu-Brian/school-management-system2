<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\Staff;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('routes', 'trips.student')->get();
        $drivers = Staff::whereHas('user.roles', function ($query) {
            $query->where('name', 'driver');
        })->where('status', '!=', 'archived')->get();

        return view('vehicles.index', compact('vehicles', 'drivers'));
    }

    public function create()
    {
        return view('vehicles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_number' => 'required|unique:vehicles,vehicle_number',
            'make' => 'nullable|string',
            'model' => 'nullable|string',
            'type' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'chassis_number' => 'nullable|string',
            'insurance_document' => 'nullable|file|mimes:pdf,jpg,png',
            'logbook_document' => 'nullable|file|mimes:pdf,jpg,png',
        ]);

        $vehicle = Vehicle::create($request->only([
            'vehicle_number',
            'make',
            'model',
            'type',
            'capacity',
            'chassis_number',
        ]));

        if ($request->hasFile('insurance_document')) {
            $vehicle->insurance_document = $request->file('insurance_document')->store('documents/insurance', 'public');
        }

        if ($request->hasFile('logbook_document')) {
            $vehicle->logbook_document = $request->file('logbook_document')->store('documents/logbook', 'public');
        }

        $vehicle->save();

        return redirect()->route('vehicles.index')->with('success', 'Vehicle added successfully.');
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

        $vehicle->update($request->only([
            'vehicle_number',
            'driver_name'
        ]));

        return redirect()->route('vehicles.index')->with('success', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('vehicles.index')->with('success', 'Vehicle deleted.');
    }
}
