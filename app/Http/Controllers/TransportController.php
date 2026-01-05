<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Student;
use App\Models\Trip;
use App\Models\StudentAssignment;
use App\Models\DropOffPoint;


class TransportController extends Controller
{
    public function index()
    {
        $students = Student::where('archive', 0)->where('is_alumni', false)->count();
        $vehicles = Vehicle::with('trips')->get();
        $trips = Trip::with(['vehicle', 'driver'])->get();
        $assignments = StudentAssignment::count();
        
        // Get active trips (trips with driver assigned)
        $activeTrips = Trip::whereNotNull('driver_id')
            ->with(['vehicle', 'driver', 'assignments'])
            ->get();
        
        // Alerts: trips without drivers
        $tripsWithoutDrivers = Trip::whereNull('driver_id')->count();
        
        // Students without assignments
        $studentsWithoutAssignments = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->whereDoesntHave('assignments', function($q) {
                $q->whereNotNull('morning_trip_id')
                  ->orWhereNotNull('evening_trip_id');
            })
            ->count();
        
        // Special assignments active
        $activeSpecialAssignments = \App\Models\TransportSpecialAssignment::where('status', 'active')
            ->where('start_date', '<=', now()->toDateString())
            ->where(function($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            })
            ->count();
    
        return view('transport.index', compact(
            'students', 
            'vehicles', 
            'trips', 
            'assignments',
            'activeTrips',
            'tripsWithoutDrivers',
            'studentsWithoutAssignments',
            'activeSpecialAssignments'
        ));
    }
    

    public function assignDriver(Request $request)
{
    $request->validate([
        'vehicle_id' => 'required|exists:vehicles,id',
        'driver_name' => 'required|string',
    ]);

    $vehicle = Vehicle::findOrFail($request->vehicle_id);
    $vehicle->update([
        'driver_name' => $request->driver_name,
    ]);

    return redirect()->back()->with('success', 'Driver assigned to vehicle successfully.');
}



    
}
