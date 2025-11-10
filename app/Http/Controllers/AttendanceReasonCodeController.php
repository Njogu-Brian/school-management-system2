<?php

namespace App\Http\Controllers;

use App\Models\AttendanceReasonCode;
use Illuminate\Http\Request;

class AttendanceReasonCodeController extends Controller
{
    /**
     * Display a listing of reason codes
     */
    public function index()
    {
        $reasonCodes = AttendanceReasonCode::orderBy('sort_order')->get();
        return view('attendance.reason_codes.index', compact('reasonCodes'));
    }

    /**
     * Show the form for creating a new reason code
     */
    public function create()
    {
        return view('attendance.reason_codes.create');
    }

    /**
     * Store a newly created reason code
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:attendance_reason_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requires_excuse' => 'nullable|boolean',
            'is_medical' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        AttendanceReasonCode::create($request->all());

        return redirect()->route('attendance.reason-codes.index')
            ->with('success', 'Reason code created successfully.');
    }

    /**
     * Show the form for editing the specified reason code
     */
    public function edit(AttendanceReasonCode $reasonCode)
    {
        return view('attendance.reason_codes.edit', compact('reasonCode'));
    }

    /**
     * Update the specified reason code
     */
    public function update(Request $request, AttendanceReasonCode $reasonCode)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:attendance_reason_codes,code,' . $reasonCode->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requires_excuse' => 'nullable|boolean',
            'is_medical' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $reasonCode->update($request->all());

        return redirect()->route('attendance.reason-codes.index')
            ->with('success', 'Reason code updated successfully.');
    }

    /**
     * Remove the specified reason code
     */
    public function destroy(AttendanceReasonCode $reasonCode)
    {
        // Check if reason code is in use
        if ($reasonCode->attendances()->count() > 0) {
            return back()->with('error', 'Cannot delete reason code that is in use. Deactivate it instead.');
        }

        $reasonCode->delete();

        return redirect()->route('attendance.reason-codes.index')
            ->with('success', 'Reason code deleted successfully.');
    }
}
