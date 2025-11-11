<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffLeaveBalance;
use App\Models\LeaveType;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use App\Models\User;

class StaffLeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $staffId = $request->get('staff_id');
        $currentYear = AcademicYear::where('is_active', true)->first();

        $query = StaffLeaveBalance::with(['staff', 'leaveType', 'academicYear']);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        if ($currentYear) {
            $query->where('academic_year_id', $currentYear->id);
        }

        $balances = $query->orderBy('staff_id')->paginate(20)->withQueryString();
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();

        return view('staff.leave_balances.index', compact('balances', 'staff', 'currentYear'));
    }

    public function show(Staff $staff)
    {
        $currentYear = AcademicYear::where('is_active', true)->first();
        
        $balances = StaffLeaveBalance::where('staff_id', $staff->id)
            ->where('academic_year_id', $currentYear?->id)
            ->with('leaveType')
            ->get();

        $leaveTypes = LeaveType::active()->get();

        return view('staff.leave_balances.show', compact('staff', 'balances', 'leaveTypes', 'currentYear'));
    }

    public function update(Request $request, StaffLeaveBalance $balance)
    {
        $request->validate([
            'entitlement_days' => 'required|integer|min:0',
            'carried_forward' => 'nullable|integer|min:0',
        ]);

        $balance->entitlement_days = $request->entitlement_days;
        $balance->carried_forward = $request->carried_forward ?? 0;
        $balance->calculateRemaining();

        return back()->with('success', 'Leave balance updated successfully.');
    }

    public function create(Request $request)
    {
        $staffId = $request->get('staff_id');
        $currentYear = AcademicYear::where('is_active', true)->first();
        $leaveTypes = LeaveType::active()->get();
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();

        return view('staff.leave_balances.create', compact('staff', 'leaveTypes', 'currentYear', 'staffId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'entitlement_days' => 'required|integer|min:0',
            'carried_forward' => 'nullable|integer|min:0',
        ]);

        $balance = StaffLeaveBalance::updateOrCreate(
            [
                'staff_id' => $request->staff_id,
                'leave_type_id' => $request->leave_type_id,
                'academic_year_id' => $request->academic_year_id,
            ],
            [
                'entitlement_days' => $request->entitlement_days,
                'carried_forward' => $request->carried_forward ?? 0,
                'used_days' => 0,
                'remaining_days' => $request->entitlement_days + ($request->carried_forward ?? 0),
            ]
        );

        return redirect()->route('staff.leave-balances.index')
            ->with('success', 'Leave balance created successfully.');
    }
}
