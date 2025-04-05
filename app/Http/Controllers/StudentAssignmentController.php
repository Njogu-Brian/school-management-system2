<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Route;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\DropOffPoint;
use App\Models\StudentAssignment;
use Illuminate\Http\Request;

class StudentAssignmentController extends Controller
{
    public function index()
    {
        $assignments = StudentAssignment::with(['student', 'vehicle', 'route', 'dropOffPoint', 'trip'])->get();
        return view('student_assignments.index', compact('assignments'));
    }

    public function create()
    {
        $students = Student::all();
        $routes = Route::all();
        $vehicles = Vehicle::all();
        $trips = Trip::all();
        $dropOffPoints = DropOffPoint::all();

        return view('student_assignments.create', compact('students', 'routes', 'vehicles', 'trips', 'dropOffPoints'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'route_id' => 'required|exists:routes,id',
            'trip_id' => 'required|exists:trips,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'drop_off_point_id' => 'required|exists:drop_off_points,id',
        ]);
        
        // Check if student is already assigned to the same trip and route
        $exists = StudentAssignment::where([
            'student_id' => $request->student_id,
            'route_id' => $request->route_id,
            'trip_id' => $request->trip_id,
        ])->exists();
        
        if ($exists) {
            return redirect()->back()->with('error', 'Student is already assigned to this trip and route.');
        }
        
        StudentAssignment::create($request->all());
        
        return redirect()->route('student_assignments.index')->with('success', 'Student assigned successfully.');
        
    }

    public function edit(StudentAssignment $assignment)
    {
        $students = Student::all();
        $routes = Route::all();
        $vehicles = Vehicle::all();
        $trips = Trip::all();
        $dropOffPoints = DropOffPoint::all();

        return view('student_assignments.edit', compact('assignment', 'students', 'routes', 'vehicles', 'trips', 'dropOffPoints'));
    }

    public function update(Request $request, StudentAssignment $assignment)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'route_id' => 'required|exists:routes,id',
            'trip_id' => 'required|exists:trips,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'drop_off_point_id' => 'required|exists:drop_off_points,id',
        ]);

        $assignment->update($request->all());

        return redirect()->route('student_assignments.index')->with('success', 'Assignment updated successfully.');
    }

    public function destroy(StudentAssignment $assignment)
    {
        $assignment->delete();
        return redirect()->route('student_assignments.index')->with('success', 'Assignment deleted successfully.');
    }
}
