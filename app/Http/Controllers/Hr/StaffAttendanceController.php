<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\BioTimePunch;
use App\Models\StaffAttendance;
use App\Services\Hr\StaffAttendanceAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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
        StaffAttendanceAccess::abortUnlessCanViewTeam();

        $date = $request->get('date', date('Y-m-d'));
        $staffId = $request->get('staff_id');

        $query = StaffAttendanceAccess::applyStaffScope(
            StaffAttendance::with(['staff', 'markedBy'])->where('date', $date)
        );

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $attendanceRecords = $query->orderBy('staff_id')->get();
        $staff = StaffAttendanceAccess::staffDropdownQuery()->get();
        $allStaff = $staff;
        $summary = $this->statusSummary($attendanceRecords);
        $canManage = StaffAttendanceAccess::canManageAttendance();
        $reportRoute = $request->route()?->getName() === 'supervisor.attendance.index'
            ? 'supervisor.attendance.report'
            : 'staff.attendance.report';
        $gateLogsRoute = str_replace('.index', '.gate-logs', $reportRoute);
        $bulkMarkRoute = $request->route()?->getName() === 'supervisor.attendance.index'
            ? null
            : 'staff.attendance.bulk-mark';

        return view('staff.attendance.index', compact(
            'attendanceRecords',
            'staff',
            'allStaff',
            'date',
            'summary',
            'canManage',
            'reportRoute',
            'gateLogsRoute',
            'bulkMarkRoute'
        ));
    }

    public function mark(Request $request)
    {
        StaffAttendanceAccess::abortUnlessCanViewTeam();
        if (! StaffAttendanceAccess::canManageAttendance()) {
            abort(403);
        }

        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,half_day',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i|after:check_in_time',
            'notes' => 'nullable|string|max:500',
        ]);

        StaffAttendance::updateOrCreate(
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
                'source' => 'manual',
            ]
        );

        return back()->with('success', 'Attendance marked successfully.');
    }

    public function bulkMark(Request $request)
    {
        StaffAttendanceAccess::abortUnlessCanViewTeam();
        if (! StaffAttendanceAccess::canManageAttendance()) {
            abort(403);
        }

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
                    'source' => 'manual',
                ]
            );
        }

        return back()->with('success', 'Bulk attendance marked successfully.');
    }

    public function report(Request $request)
    {
        StaffAttendanceAccess::abortUnlessCanViewTeam();

        $staffId = $request->get('staff_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $query = StaffAttendanceAccess::applyStaffScope(
            StaffAttendance::with('staff')->whereBetween('date', [$startDate, $endDate])
        );

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
        $staff = StaffAttendanceAccess::staffDropdownQuery()->get();
        $canManage = StaffAttendanceAccess::canManageAttendance();
        $reportRoute = $request->route()?->getName() ?? 'staff.attendance.report';
        $indexRoute = str_contains($reportRoute, 'senior_teacher')
            ? 'senior_teacher.staff_attendance.report'
            : (str_contains($reportRoute, 'supervisor') ? 'supervisor.attendance.report' : 'staff.attendance.report');
        $gateLogsRoute = str_replace('.report', '.gate-logs', $indexRoute);

        return view('staff.attendance.report', compact(
            'attendance',
            'staff',
            'startDate',
            'endDate',
            'summary',
            'mapPoints',
            'schoolGeofence',
            'canManage',
            'reportRoute',
            'gateLogsRoute'
        ));
    }

    public function gateLogs(Request $request)
    {
        StaffAttendanceAccess::abortUnlessCanViewTeam();

        $staffId = $request->get('staff_id');
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());
        $multipleOnly = $request->boolean('multiple_only');

        $query = StaffAttendanceAccess::applyStaffScope(
            BioTimePunch::with('staff')->whereDate('punch_time', '>=', $startDate)->whereDate('punch_time', '<=', $endDate),
            'staff_id'
        );

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        if ($multipleOnly) {
            $multiDayKeys = BioTimePunch::query()
                ->selectRaw('staff_id, DATE(punch_time) as punch_date')
                ->whereDate('punch_time', '>=', $startDate)
                ->whereDate('punch_time', '<=', $endDate)
                ->whereNotNull('staff_id')
                ->groupBy('staff_id', 'punch_date')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->map(fn ($row) => $row->staff_id.'|'.$row->punch_date);

            if ($multiDayKeys->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($multiDayKeys) {
                    foreach ($multiDayKeys as $key) {
                        [$sid, $day] = explode('|', $key, 2);
                        $q->orWhere(function ($inner) use ($sid, $day) {
                            $inner->where('staff_id', $sid)->whereDate('punch_time', $day);
                        });
                    }
                });
            }
        }

        $punches = $query->orderByDesc('punch_time')->paginate(100)->withQueryString();
        $roleMap = $this->gatePunchRoleMap($startDate, $endDate, $staffId);
        $staff = StaffAttendanceAccess::staffDropdownQuery()->get();

        $reportRoute = $request->route()?->getName() ?? 'staff.attendance.gate-logs';
        $gateLogsRoute = $reportRoute;
        $indexReportRoute = str_replace('.gate-logs', '.report', $reportRoute);

        return view('staff.attendance.gate-logs', compact(
            'punches',
            'staff',
            'startDate',
            'endDate',
            'multipleOnly',
            'roleMap',
            'gateLogsRoute',
            'indexReportRoute'
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

        $myGateLogs = BioTimePunch::query()
            ->where('staff_id', $user->staff->id)
            ->whereDate('punch_time', '>=', $startDate)
            ->whereDate('punch_time', '<=', $endDate)
            ->orderByDesc('punch_time')
            ->limit(200)
            ->get();

        $roleMap = $this->gatePunchRoleMap($startDate, $endDate, (string) $user->staff->id);

        return view('staff.attendance.my-report', [
            'attendance' => $attendance,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'summary' => $summary,
            'staffName' => $user->staff->full_name,
            'myGateLogs' => $myGateLogs,
            'roleMap' => $roleMap,
        ]);
    }

    /**
     * @return array<int, string> punch_id => role label
     */
    private function gatePunchRoleMap(string $startDate, string $endDate, ?string $staffId = null): array
    {
        $query = BioTimePunch::query()
            ->whereDate('punch_time', '>=', $startDate)
            ->whereDate('punch_time', '<=', $endDate)
            ->whereNotNull('staff_id')
            ->orderBy('punch_time');

        $query = StaffAttendanceAccess::applyStaffScope($query, 'staff_id');

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $groups = $query->get()->groupBy(function (BioTimePunch $punch) {
            return $punch->staff_id.'|'.Carbon::parse($punch->punch_time)->toDateString();
        });

        $map = [];
        foreach ($groups as $rows) {
            /** @var Collection<int, BioTimePunch> $rows */
            $sorted = $rows->sortBy('punch_time')->values();
            $count = $sorted->count();
            foreach ($sorted as $index => $punch) {
                if ($index === 0 && $count === 1) {
                    $map[$punch->id] = 'check_in_only';
                } elseif ($index === 0) {
                    $map[$punch->id] = 'check_in';
                } elseif ($index === $count - 1) {
                    $map[$punch->id] = 'check_out';
                } else {
                    $map[$punch->id] = 'extra';
                }
            }
        }

        return $map;
    }
}
