<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Department;
use App\Models\StaffCategory;
use App\Models\StaffLeaveBalance;
use App\Models\LeaveRequest;
use App\Models\StaffAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HRAnalyticsController extends Controller
{
    /**
     * Display HR Analytics Dashboard
     */
    public function index()
    {
        // Total staff count
        $totalStaff = Staff::count();
        $activeStaff = Staff::where('status', 'active')->count();
        $archivedStaff = Staff::where('status', 'archived')->count();
        $onLeaveStaff = Staff::where('employment_status', 'on_leave')->count();

        // Staff by department (for chart)
        $staffByDepartment = Department::withCount('staff')
            ->get()
            ->map(function ($dept) {
                return [
                    'name' => $dept->name,
                    'count' => $dept->staff_count
                ];
            });

        // Staff by category (for chart)
        $staffByCategory = StaffCategory::withCount('staff')
            ->get()
            ->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'count' => $cat->staff_count
                ];
            });

        // New hires this month
        $newHiresThisMonth = Staff::whereMonth('hire_date', Carbon::now()->month)
            ->whereYear('hire_date', Carbon::now()->year)
            ->count();

        // New hires this year
        $newHiresThisYear = Staff::whereYear('hire_date', Carbon::now()->year)->count();

        // Terminations this month
        $terminationsThisMonth = Staff::whereNotNull('termination_date')
            ->whereMonth('termination_date', Carbon::now()->month)
            ->whereYear('termination_date', Carbon::now()->year)
            ->count();

        // Leave utilization
        $leaveUtilization = $this->getLeaveUtilization();

        // Attendance statistics
        $attendanceStats = $this->getAttendanceStats();

        // Employment status breakdown
        $employmentStatusBreakdown = Staff::select('employment_status', DB::raw('count(*) as count'))
            ->where('status', 'active')
            ->groupBy('employment_status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => ucfirst(str_replace('_', ' ', $item->employment_status ?? 'active')),
                    'count' => $item->count
                ];
            });

        // Recent hires (last 5)
        $recentHires = Staff::where('status', 'active')
            ->whereNotNull('hire_date')
            ->orderBy('hire_date', 'desc')
            ->limit(5)
            ->with(['department', 'jobTitle'])
            ->get();

        // Upcoming contract renewals (next 30 days)
        $upcomingRenewals = Staff::where('status', 'active')
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [Carbon::now(), Carbon::now()->addDays(30)])
            ->orderBy('contract_end_date', 'asc')
            ->with(['department', 'jobTitle'])
            ->get();

        return view('hr.analytics.index', compact(
            'totalStaff',
            'activeStaff',
            'archivedStaff',
            'onLeaveStaff',
            'staffByDepartment',
            'staffByCategory',
            'newHiresThisMonth',
            'newHiresThisYear',
            'terminationsThisMonth',
            'leaveUtilization',
            'attendanceStats',
            'employmentStatusBreakdown',
            'recentHires',
            'upcomingRenewals'
        ));
    }

    /**
     * Get leave utilization statistics
     */
    private function getLeaveUtilization()
    {
        $currentYear = Carbon::now()->year;

        // Get all leave balances (simplified - get all active balances)
        $leaveBalances = StaffLeaveBalance::all();

        $totalEntitlement = $leaveBalances->sum('entitlement_days');
        $totalUsed = $leaveBalances->sum('used_days');
        $totalRemaining = $leaveBalances->sum('remaining_days');
        $utilizationRate = $totalEntitlement > 0 ? ($totalUsed / $totalEntitlement) * 100 : 0;

        // Pending leave requests
        $pendingRequests = LeaveRequest::where('status', 'pending')->count();
        $approvedRequests = LeaveRequest::where('status', 'approved')
            ->whereYear('created_at', $currentYear)
            ->count();

        return [
            'total_entitlement' => $totalEntitlement,
            'total_used' => $totalUsed,
            'total_remaining' => $totalRemaining,
            'utilization_rate' => round($utilizationRate, 2),
            'pending_requests' => $pendingRequests,
            'approved_requests' => $approvedRequests,
        ];
    }

    /**
     * Get attendance statistics
     */
    private function getAttendanceStats()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Get attendance for current month
        $attendance = StaffAttendance::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();

        $totalRecords = $attendance->count();
        $present = $attendance->where('status', 'present')->count();
        $absent = $attendance->where('status', 'absent')->count();
        $late = $attendance->where('status', 'late')->count();
        $halfDay = $attendance->where('status', 'half_day')->count();

        $attendanceRate = $totalRecords > 0 ? ($present / $totalRecords) * 100 : 0;

        // Today's attendance
        $todayAttendance = StaffAttendance::whereDate('date', Carbon::today())->get();
        $presentToday = $todayAttendance->where('status', 'present')->count();
        $absentToday = $todayAttendance->where('status', 'absent')->count();

        return [
            'total_records' => $totalRecords,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'half_day' => $halfDay,
            'attendance_rate' => round($attendanceRate, 2),
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
        ];
    }
}

