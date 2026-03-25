<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\Payment;
use App\Models\SchoolDay;
use App\Services\StudentBalanceService;
use Illuminate\Http\Request;

class ApiDashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();

        if ($user && $user->hasAnyRole(['Parent', 'Guardian'])) {
            $ids = $user->accessibleStudentIds();
            $totalBalance = 0.0;
            foreach ($ids as $sid) {
                $s = Student::find($sid);
                if ($s) {
                    $totalBalance += (float) StudentBalanceService::getTotalOutstandingBalance($s);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'role' => 'parent',
                    'children_count' => count($ids),
                    'total_fee_balance' => round($totalBalance, 2),
                ],
            ]);
        }

        $today = now()->toDateString();

        $totalStudents = Student::where('archive', 0)->where('is_alumni', false)->count();
        $totalStaff = Staff::count();
        $presentToday = SchoolDay::isSchoolDay($today)
            ? Attendance::whereDate('date', $today)->where('status', 'present')->count()
            : 0;
        $feesCollected = Payment::where(function ($q) {
            $q->whereNull('reversed')->orWhere('reversed', false);
        })->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'total_students' => $totalStudents,
                'total_staff' => $totalStaff,
                'present_today' => $presentToday,
                'fees_collected' => round($feesCollected, 2),
            ],
        ]);
    }
}
