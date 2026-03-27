<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    private function statusSummary($records): array
    {
        return [
            'total' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'late' => $records->where('status', 'late')->count(),
            'half_day' => $records->where('status', 'half_day')->count(),
        ];
    }

    public function index(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $staffId = $request->get('staff_id');

        $query = StaffAttendance::with(['staff', 'markedBy'])
            ->where('date', $date);

        // Supervisors can only see their subordinates' attendance
        if (is_supervisor() && !auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) {
            $subordinateIds = get_subordinate_staff_ids();
            if (!empty($subordinateIds)) {
                $query->whereIn('staff_id', $subordinateIds);
            } else {
                $query->whereRaw('1 = 0'); // No subordinates, show nothing
            }
        }

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $attendanceRecords = $query->orderBy('staff_id')->get();
        
        // Supervisors see only their subordinates
        if (is_supervisor() && !auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) {
            $subordinateIds = get_subordinate_staff_ids();
            $staff = Staff::where('status', 'active')
                ->whereIn('id', $subordinateIds)
                ->orderBy('first_name')
                ->get();
            $allStaff = $staff;
        } else {
            $staff = Staff::where('status', 'active')->orderBy('first_name')->get();
            $allStaff = Staff::where('status', 'active')->orderBy('first_name')->get();
        }

        $summary = $this->statusSummary($attendanceRecords);

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

        // Supervisors can only see their subordinates' attendance
        if (is_supervisor() && !auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) {
            $subordinateIds = get_subordinate_staff_ids();
            if (!empty($subordinateIds)) {
                $query->whereIn('staff_id', $subordinateIds);
            } else {
                $query->whereRaw('1 = 0'); // No subordinates, show nothing
            }
        }

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $summaryBase = clone $query;
        $summary = [
            'total' => (clone $summaryBase)->count(),
            'present' => (clone $summaryBase)->where('status', 'present')->count(),
            'absent' => (clone $summaryBase)->where('status', 'absent')->count(),
            'late' => (clone $summaryBase)->where('status', 'late')->count(),
            'half_day' => (clone $summaryBase)->where('status', 'half_day')->count(),
        ];

        $mapRows = (clone $query)
            ->where(function ($q) {
                $q->whereNotNull('check_in_latitude')
                    ->whereNotNull('check_in_longitude')
                    ->orWhere(function ($sq) {
                        $sq->whereNotNull('check_out_latitude')
                            ->whereNotNull('check_out_longitude');
                    });
            })
            ->orderBy('date', 'desc')
            ->limit(400)
            ->get();

        $mapPoints = [];
        foreach ($mapRows as $row) {
            $staffName = $row->staff?->full_name ?? 'Staff';
            $dateLabel = $row->date ? Carbon::parse($row->date)->format('Y-m-d') : null;

            if ($row->check_in_latitude !== null && $row->check_in_longitude !== null) {
                $mapPoints[] = [
                    'type' => 'check_in',
                    'staff_name' => $staffName,
                    'date' => $dateLabel,
                    'time' => $row->check_in_time ? Carbon::parse($row->check_in_time)->format('H:i') : null,
                    'distance_meters' => $row->check_in_distance_meters !== null ? (float) $row->check_in_distance_meters : null,
                    'lat' => (float) $row->check_in_latitude,
                    'lng' => (float) $row->check_in_longitude,
                ];
            }

            if ($row->check_out_latitude !== null && $row->check_out_longitude !== null) {
                $mapPoints[] = [
                    'type' => 'check_out',
                    'staff_name' => $staffName,
                    'date' => $dateLabel,
                    'time' => $row->check_out_time ? Carbon::parse($row->check_out_time)->format('H:i') : null,
                    'distance_meters' => $row->check_out_distance_meters !== null ? (float) $row->check_out_distance_meters : null,
                    'lat' => (float) $row->check_out_latitude,
                    'lng' => (float) $row->check_out_longitude,
                ];
            }
        }

        $schoolGeofence = [
            'latitude' => setting('school_geofence_latitude') !== null ? (float) setting('school_geofence_latitude') : null,
            'longitude' => setting('school_geofence_longitude') !== null ? (float) setting('school_geofence_longitude') : null,
            'radius_meters' => (float) setting('school_geofence_radius_meters', '100'),
        ];

        $attendance = $query->orderBy('date', 'desc')->paginate(50)->withQueryString();
        
        // Supervisors see only their subordinates
        if (is_supervisor() && !auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) {
            $subordinateIds = get_subordinate_staff_ids();
            $staff = Staff::where('status', 'active')
                ->whereIn('id', $subordinateIds)
                ->orderBy('first_name')
                ->get();
        } else {
            $staff = Staff::where('status', 'active')->orderBy('first_name')->get();
        }

        return view('staff.attendance.report', compact(
            'attendance',
            'staff',
            'startDate',
            'endDate',
            'summary',
            'mapPoints',
            'schoolGeofence'
        ));
    }

    public function myReport(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! $user->staff) {
            abort(403, 'No staff profile is linked to this account.');
        }

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $attendance = StaffAttendance::with('staff')
            ->where('staff_id', $user->staff->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->paginate(50)
            ->withQueryString();

        $summary = $this->statusSummary(collect($attendance->items()));

        return view('staff.attendance.my-report', [
            'attendance' => $attendance,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $summary,
            'staffName' => $user->staff->full_name,
        ]);
    }
}
