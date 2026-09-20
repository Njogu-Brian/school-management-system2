<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use App\Models\Staff;
use App\Services\ImageOptimizer;

class VehicleController extends Controller
{
    public function index()
    {
        $vehicles = Vehicle::with('trips.assignments.student')->get();
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
            'driver_name' => 'nullable|string|max:255',
            'make' => 'nullable|string',
            'model' => 'nullable|string',
            'type' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'chassis_number' => 'nullable|string',
            'insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'logbook_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $vehicle = Vehicle::create($request->only([
            'vehicle_number',
            'make',
            'model',
            'type',
            'capacity',
            'chassis_number',
            'driver_name',
        ]));

        $this->storeVehicleFiles($request, $vehicle);

        return redirect()->route('transport.vehicles.index')->with('success', 'Vehicle added successfully.');
    }

    public function edit(Vehicle $vehicle)
    {
        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $request->validate([
            'vehicle_number' => 'required|unique:vehicles,vehicle_number,' . $vehicle->id,
            'driver_name' => 'nullable|string|max:255',
            'make' => 'nullable|string',
            'model' => 'nullable|string',
            'type' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'chassis_number' => 'nullable|string',
            'insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'logbook_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $vehicle->update($request->only([
            'vehicle_number',
            'driver_name',
            'make',
            'model',
            'type',
            'capacity',
            'chassis_number',
        ]));

        $this->storeVehicleFiles($request, $vehicle);

        return redirect()->route('transport.vehicles.index')->with('success', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return redirect()->route('transport.vehicles.index')->with('success', 'Vehicle deleted.');
    }

    protected function storeVehicleFiles(Request $request, Vehicle $vehicle): void
    {
        $disk = config('filesystems.public_disk', 'public');

        if ($request->hasFile('insurance_document')) {
            $vehicle->insurance_document = $request->file('insurance_document')->store('documents/insurance', $disk);
        }

        if ($request->hasFile('logbook_document')) {
            $vehicle->logbook_document = $request->file('logbook_document')->store('documents/logbook', $disk);
        }

        if ($request->hasFile('photo')) {
            if ($vehicle->photo) {
                try {
                    storage_public()->delete($vehicle->photo);
                } catch (\Throwable $e) {
                    // Ignore missing previous file.
                }
            }
            $path = $request->file('photo')->store('vehicle_photos', $disk);
            $vehicle->photo = $path;

            if ($disk === 'public') {
                $full = storage_path('app/public/'.$path);
                try {
                    app(ImageOptimizer::class)->optimize($full, 1600, 1200);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $vehicle->save();
    }
}
