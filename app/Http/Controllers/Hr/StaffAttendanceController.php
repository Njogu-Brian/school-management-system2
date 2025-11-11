<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $staffId = $request->get('staff_id');

        $query = StaffAttendance::with(['staff', 'markedBy'])
            ->where('date', $date);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $attendanceRecords = $query->orderBy('staff_id')->get();
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();
        
        // Get all active staff for marking (even if they don't have attendance record yet)
        $allStaff = Staff::where('status', 'active')->orderBy('first_name')->get();

        // Get attendance summary
        $summary = [
            'total' => $attendanceRecords->count(),
            'present' => $attendanceRecords->where('status', 'present')->count(),
            'absent' => $attendanceRecords->where('status', 'absent')->count(),
            'late' => $attendanceRecords->where('status', 'late')->count(),
            'half_day' => $attendanceRecords->where('status', 'half_day')->count(),
        ];

        return view('staff.attendance.index', compact('attendanceRecords', 'staff', 'allStaff', 'date', 'summary'));
    }

    public function mark(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,half_day',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendance = StaffAttendance::updateOrCreate(
            [
                'staff_id' => $request->staff_id,
                'date' => $request->date,
            ],
            [
                'status' => $request->status,
                'check_in_time' => $request->check_in_time,
                'check_out_time' => $request->check_out_time,
                'notes' => $request->notes,
                'marked_by' => auth()->id(),
            ]
        );

        return back()->with('success', 'Attendance marked successfully.');
    }

    public function bulkMark(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.staff_id' => 'required|exists:staff,id',
            'attendance.*.status' => 'required|in:present,absent,late,half_day',
        ]);

        foreach ($request->attendance as $record) {
            StaffAttendance::updateOrCreate(
                [
                    'staff_id' => $record['staff_id'],
                    'date' => $request->date,
                ],
                [
                    'status' => $record['status'],
                    'check_in_time' => $record['check_in_time'] ?? null,
                    'check_out_time' => $record['check_out_time'] ?? null,
                    'notes' => $record['notes'] ?? null,
                    'marked_by' => auth()->id(),
                ]
            );
        }

        return back()->with('success', 'Bulk attendance marked successfully.');
    }

    public function report(Request $request)
    {
        $staffId = $request->get('staff_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $query = StaffAttendance::with('staff')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $attendance = $query->orderBy('date', 'desc')->paginate(50)->withQueryString();
        $staff = Staff::where('status', 'active')->orderBy('first_name')->get();

        return view('staff.attendance.report', compact('attendance', 'staff', 'startDate', 'endDate'));
    }
}
