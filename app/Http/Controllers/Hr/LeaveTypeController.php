<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $leaveTypes = LeaveType::orderBy('name')->get();
        return view('staff.leave_types.index', compact('leaveTypes'));
    }

    public function create()
    {
        return view('staff.leave_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:leave_types,code',
            'max_days' => 'nullable|integer|min:0',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        LeaveType::create($request->all());

        return redirect()->route('staff.leave-types.index')
            ->with('success', 'Leave type created successfully.');
    }

    public function edit(LeaveType $leaveType)
    {
        return view('staff.leave_types.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:leave_types,code,' . $leaveType->id,
            'max_days' => 'nullable|integer|min:0',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $leaveType->update($request->all());

        return redirect()->route('staff.leave-types.index')
            ->with('success', 'Leave type updated successfully.');
    }

    public function destroy(LeaveType $leaveType)
    {
        // Check if leave type is being used
        if ($leaveType->leaveRequests()->count() > 0 || $leaveType->leaveBalances()->count() > 0) {
            return back()->with('error', 'Cannot delete leave type that has associated records.');
        }

        $leaveType->delete();

        return redirect()->route('staff.leave-types.index')
            ->with('success', 'Leave type deleted successfully.');
    }
}
