<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\StaffLeaveBalance;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * Display leave requests and balances
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        if (!$staff) {
            abort(403, 'No staff record found.');
        }
        
        $currentYear = AcademicYear::where('is_active', true)->first();
        
        // Get leave requests for this staff
        $leaveRequests = LeaveRequest::where('staff_id', $staff->id)
            ->with(['leaveType', 'approvedBy', 'rejectedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Get leave balances
        $leaveBalances = StaffLeaveBalance::where('staff_id', $staff->id)
            ->when($currentYear, function($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })
            ->with('leaveType')
            ->get();
        
        // Get available leave types
        $leaveTypes = LeaveType::active()->orderBy('name')->get();
        
        return view('teacher.leave.index', compact('leaveRequests', 'leaveBalances', 'leaveTypes', 'staff', 'currentYear'));
    }
    
    /**
     * Show form to create leave request
     */
    public function create()
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        if (!$staff) {
            abort(403, 'No staff record found.');
        }
        
        $currentYear = AcademicYear::where('is_active', true)->first();
        $leaveTypes = LeaveType::active()->orderBy('name')->get();
        
        // Get leave balances for display
        $leaveBalances = StaffLeaveBalance::where('staff_id', $staff->id)
            ->when($currentYear, function($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })
            ->with('leaveType')
            ->get();
        
        return view('teacher.leave.create', compact('leaveTypes', 'leaveBalances', 'staff', 'currentYear'));
    }
    
    /**
     * Store leave request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        if (!$staff) {
            abort(403, 'No staff record found.');
        }
        
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);
        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        // Calculate working days (excluding weekends)
        $daysRequested = $this->calculateWorkingDays($startDate, $endDate);
        
        // Check leave balance
        $currentYear = AcademicYear::where('is_active', true)->first();
        $balance = StaffLeaveBalance::where('staff_id', $staff->id)
            ->where('leave_type_id', $request->leave_type_id)
            ->when($currentYear, function($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })
            ->first();
        
        if ($balance && $balance->remaining_days < $daysRequested) {
            return back()->withInput()->with('error', 
                "Insufficient leave balance. Available: {$balance->remaining_days} days, Requested: {$daysRequested} days."
            );
        }
        
        $leaveRequest = LeaveRequest::create([
            'staff_id' => $staff->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $daysRequested,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);
        
        return redirect()->route('teacher.leave.index')
            ->with('success', 'Leave request submitted successfully.');
    }
    
    /**
     * Show leave request details
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        if (!$staff || $leaveRequest->staff_id != $staff->id) {
            abort(403, 'You do not have access to this leave request.');
        }
        
        $leaveRequest->load(['leaveType', 'approvedBy', 'rejectedBy']);
        
        return view('teacher.leave.show', compact('leaveRequest'));
    }
    
    /**
     * Cancel leave request
     */
    public function cancel(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();
        $staff = $user->staff;
        
        if (!$staff || $leaveRequest->staff_id != $staff->id) {
            abort(403, 'You do not have access to this leave request.');
        }
        
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be cancelled.');
        }
        
        $leaveRequest->update(['status' => 'cancelled']);
        
        return back()->with('success', 'Leave request cancelled successfully.');
    }
    
    /**
     * Calculate working days (excluding weekends)
     */
    private function calculateWorkingDays(Carbon $start, Carbon $end)
    {
        $days = 0;
        $current = $start->copy();
        
        while ($current <= $end) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($current->dayOfWeek != Carbon::SATURDAY && $current->dayOfWeek != Carbon::SUNDAY) {
                $days++;
            }
            $current->addDay();
        }
        
        return $days;
    }
}
