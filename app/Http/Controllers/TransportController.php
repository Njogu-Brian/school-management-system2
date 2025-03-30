<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicle;
use App\Models\Route as TransportRoute;
use App\Models\Student;
use App\Models\Trip;
use App\Models\StudentAssignment;


class TransportController extends Controller
{
    public function index()
    {
        $students = Student::all();
        $vehicles = Vehicle::all();
        $routes = TransportRoute::all();

        return view('transport.index', compact('students', 'vehicles', 'routes'));
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

public function assignStudentToRoute(Request $request)
{
    $request->validate([
        'student_id' => 'required|exists:students,id',
        'route_id' => 'required|exists:routes,id',
        'trip_id' => 'required|exists:trips,id',
        'drop_off_point_id' => 'required|exists:drop_off_points,id',
        'vehicle_id' => 'nullable|exists:vehicles,id',
    ]);

    StudentAssignment::create([
        'student_id' => $request->student_id,
        'route_id' => $request->route_id,
        'trip_id' => $request->trip_id,
        'drop_off_point_id' => $request->drop_off_point_id,
        'vehicle_id' => $request->vehicle_id,
    ]);

    return back()->with('success', 'Student successfully assigned to route and trip.');
}

    
}
