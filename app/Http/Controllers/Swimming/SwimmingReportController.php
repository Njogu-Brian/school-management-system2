<?php

namespace App\Http\Controllers\Swimming;

use App\Http\Controllers\Controller;
use App\Models\{SwimmingAttendance, SwimmingWallet, SwimmingLedger};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SwimmingReportController extends Controller
{
    /**
     * Daily attendance report
     */
    public function dailyAttendance(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $classroomId = $request->get('classroom_id');
        
        $query = SwimmingAttendance::with(['student', 'classroom'])
            ->join('students', 'swimming_attendance.student_id', '=', 'students.id')
            ->whereDate('swimming_attendance.attendance_date', $date);
        
        if ($classroomId) {
            $query->where('swimming_attendance.classroom_id', $classroomId);
        }
        
        $attendance = $query->select('swimming_attendance.*')
            ->orderBy('swimming_attendance.classroom_id')
            ->orderBy('students.first_name')
            ->get()
            ->groupBy('classroom_id');
        
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        
        return view('swimming.reports.daily_attendance', [
            'attendance' => $attendance,
            'classrooms' => $classrooms,
            'selected_date' => $date,
            'selected_classroom_id' => $classroomId,
        ]);
    }

    /**
     * Unpaid sessions report
     */
    public function unpaidSessions(Request $request)
    {
        $query = SwimmingAttendance::with(['student.classroom'])
            ->where('payment_status', 'unpaid');
        
        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }
        
        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }
        
        $unpaid = $query->orderBy('attendance_date', 'desc')
            ->paginate(50);
        
        $totalUnpaid = $query->sum('session_cost');
        $totalCount = $query->count();
        
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        
        return view('swimming.reports.unpaid_sessions', [
            'unpaid' => $unpaid,
            'classrooms' => $classrooms,
            'total_unpaid' => $totalUnpaid,
            'total_count' => $totalCount,
            'filters' => $request->only(['date_from', 'date_to', 'classroom_id']),
        ]);
    }

    /**
     * Wallet balances report
     */
    public function walletBalances(Request $request)
    {
        $query = SwimmingWallet::with(['student.classroom']);
        
        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        }
        
        if ($request->filled('balance_filter')) {
            if ($request->balance_filter === 'positive') {
                $query->where('balance', '>', 0);
            } elseif ($request->balance_filter === 'zero') {
                $query->where('balance', '=', 0);
            } elseif ($request->balance_filter === 'negative') {
                $query->where('balance', '<', 0);
            }
        }
        
        $wallets = $query->orderBy('balance', 'desc')
            ->paginate(50);
        
        $totalBalance = SwimmingWallet::sum('balance');
        $totalCredited = SwimmingWallet::sum('total_credited');
        $totalDebited = SwimmingWallet::sum('total_debited');
        
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        
        return view('swimming.reports.wallet_balances', [
            'wallets' => $wallets,
            'classrooms' => $classrooms,
            'total_balance' => $totalBalance,
            'total_credited' => $totalCredited,
            'total_debited' => $totalDebited,
            'filters' => $request->only(['classroom_id', 'balance_filter']),
        ]);
    }

    /**
     * Revenue vs sessions report
     */
    public function revenueVsSessions(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        
        // Revenue from credits
        $revenue = SwimmingLedger::where('type', 'credit')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->sum('amount');
        
        // Sessions consumed
        $sessions = SwimmingAttendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->count();
        
        $paidSessions = SwimmingAttendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->where('payment_status', 'paid')
            ->count();
        
        $unpaidSessions = SwimmingAttendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->where('payment_status', 'unpaid')
            ->count();
        
        $totalSessionCost = SwimmingAttendance::whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->where('payment_status', 'paid')
            ->sum('session_cost');
        
        return view('swimming.reports.revenue_vs_sessions', [
            'revenue' => $revenue,
            'sessions' => $sessions,
            'paid_sessions' => $paidSessions,
            'unpaid_sessions' => $unpaidSessions,
            'total_session_cost' => $totalSessionCost,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }
}
