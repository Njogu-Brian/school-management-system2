<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Staff;
use App\Models\StaffLeaveBalance;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with(['staff', 'leaveType', 'approvedBy', 'rejectedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by staff
        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->end_date);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();

        return view('staff.leave_requests.index', compact('leaveRequests', 'staff'));
    }

    public function create()
    {
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();
        $leaveTypes = LeaveType::active()->orderBy('name')->get();
        $currentYear = AcademicYear::where('is_active', true)->first();

        return view('staff.leave_requests.create', compact('staff', 'leaveTypes', 'currentYear'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
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
        $balance = StaffLeaveBalance::where('staff_id', $request->staff_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('academic_year_id', $currentYear?->id)
            ->first();

        if ($balance && $balance->remaining_days < $daysRequested) {
            return back()->withInput()->with('error', 
                "Insufficient leave balance. Available: {$balance->remaining_days} days, Requested: {$daysRequested} days."
            );
        }

        $leaveRequest = LeaveRequest::create([
            'staff_id' => $request->staff_id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $daysRequested,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return redirect()->route('staff.leave-requests.index')
            ->with('success', 'Leave request submitted successfully.');
    }

    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['staff', 'leaveType', 'approvedBy', 'rejectedBy']);
        return view('staff.leave_requests.show', compact('leaveRequest'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be approved.');
        }

        DB::beginTransaction();
        try {
            // Update leave request
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'admin_notes' => $request->admin_notes,
            ]);

            // Update leave balance
            $currentYear = AcademicYear::where('is_active', true)->first();
            $balance = StaffLeaveBalance::firstOrCreate(
                [
                    'staff_id' => $leaveRequest->staff_id,
                    'leave_type_id' => $leaveRequest->leave_type_id,
                    'academic_year_id' => $currentYear?->id,
                ],
                [
                    'entitlement_days' => 0,
                    'used_days' => 0,
                    'remaining_days' => 0,
                    'carried_forward' => 0,
                ]
            );

            $balance->used_days += $leaveRequest->days_requested;
            $balance->calculateRemaining();
            $balance->save();

            DB::commit();

            return back()->with('success', 'Leave request approved successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error approving leave request: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be rejected.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('success', 'Leave request rejected.');
    }

    public function cancel(LeaveRequest $leaveRequest)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be cancelled.');
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Leave request cancelled.');
    }

    /**
     * Calculate working days between two dates (excluding weekends)
     */
    private function calculateWorkingDays(Carbon $start, Carbon $end): int
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
