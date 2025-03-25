<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trip;
use App\Http\Controllers\Controller;

class TripController extends Controller
{
    public function index()
    {
        $trips = Trip::all();
        return view('trips.index', compact('trips'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'transport_id' => 'required|exists:transports,id',
        ]);

        Trip::create([
            'student_id' => $request->student_id,
            'transport_id' => $request->transport_id,
        ]);

        return redirect()->back()->with('success', 'Trip created successfully.');
    }
}