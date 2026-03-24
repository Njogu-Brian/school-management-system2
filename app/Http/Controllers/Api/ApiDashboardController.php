<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\Payment;
use Illuminate\Http\Request;

class ApiDashboardController extends Controller
{
    public function stats(Request $request)
    {
        $today = now()->toDateString();

        $totalStudents = Student::where('archive', 0)->where('is_alumni', false)->count();
        $totalStaff = Staff::count();
        $presentToday = Attendance::whereDate('date', $today)->where('status', 'present')->count();
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
