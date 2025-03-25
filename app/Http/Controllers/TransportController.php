<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transport;
use App\Models\Student;
use App\Models\Trip;
use App\Http\Controllers\Controller;

class TransportController extends Controller
{
    public function index()
    {
        // Fetch all students
        $students = Student::all();

        // Fetch all transport records
        $transports = Transport::all();

        return view('transport.index', compact('students', 'transports'));
    }

    public function assignDriver(Request $request)
    {
        $request->validate([
            'driver_name' => 'required|string',
            'vehicle_number' => 'required|string',
        ]);

        Transport::create([
            'driver_name' => $request->driver_name,
            'vehicle_number' => $request->vehicle_number,
        ]);

        return redirect()->back()->with('success', 'Driver assigned to vehicle successfully.');
    }

    public function assignStudentToRoute(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'transport_id' => 'required|exists:transports,id',
        ]);

        Trip::create([
            'student_id' => $request->student_id,
            'transport_id' => $request->transport_id,
        ]);

        return redirect()->back()->with('success', 'Student assigned to route successfully.');
    }
}