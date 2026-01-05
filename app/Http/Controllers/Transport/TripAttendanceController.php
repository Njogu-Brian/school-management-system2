<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripAttendance;
use App\Services\TransportAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TripAttendanceController extends Controller
{
    protected $assignmentService;

    public function __construct(TransportAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Show attendance checklist for a trip
     */
    public function create(Trip $trip, Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $carbonDate = Carbon::parse($date);

        // Only allow attendance on school days
        if (!$this->assignmentService->isSchoolDay($carbonDate)) {
            return redirect()->back()->with('error', 'Cannot take attendance on non-school days.');
        }

        $students = $this->assignmentService->getStudentsForTrip($trip, $carbonDate);
        
        // Group by class, sorted alphabetically within each class
        $studentsByClass = $students->sortBy('first_name')
            ->groupBy(function ($student) {
                return $student->classroom->name ?? 'Unassigned';
            });

        // Get existing attendance records
        $attendanceRecords = TripAttendance::where('trip_id', $trip->id)
            ->whereDate('attendance_date', $date)
            ->get()
            ->keyBy('student_id');

        $trip->load(['vehicle', 'driver', 'stops.dropOffPoint']);

        return view('transport.trip_attendance.create', [
            'trip' => $trip,
            'students' => $students,
            'students_by_class' => $studentsByClass,
            'attendance_records' => $attendanceRecords,
            'selected_date' => $date,
        ]);
    }

    /**
     * Store attendance records
     */
    public function store(Request $request, Trip $trip)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late',
            'attendance.*.boarded_at' => 'nullable|date_format:H:i',
            'attendance.*.notes' => 'nullable|string',
        ]);

        $date = $request->input('date');
        $carbonDate = Carbon::parse($date);

        if (!$this->assignmentService->isSchoolDay($carbonDate)) {
            return redirect()->back()->with('error', 'Cannot take attendance on non-school days.');
        }

        DB::transaction(function () use ($request, $trip, $date) {
            foreach ($request->input('attendance') as $attendanceData) {
                TripAttendance::updateOrCreate(
                    [
                        'trip_id' => $trip->id,
                        'student_id' => $attendanceData['student_id'],
                        'attendance_date' => $date,
                    ],
                    [
                        'status' => $attendanceData['status'],
                        'boarded_at' => $attendanceData['boarded_at'] ?? null,
                        'notes' => $attendanceData['notes'] ?? null,
                        'marked_by' => auth()->id(),
                    ]
                );
            }
        });

        return redirect()->route('transport.trip-attendance.create', ['trip' => $trip->id, 'date' => $date])
            ->with('success', 'Attendance recorded successfully.');
    }

    /**
     * View attendance history for a trip
     */
    public function index(Trip $trip, Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $attendance = TripAttendance::where('trip_id', $trip->id)
            ->whereDate('attendance_date', $date)
            ->with(['student.classroom'])
            ->get()
            ->sortBy('student.first_name');

        $trip->load(['vehicle', 'driver']);

        return view('transport.trip_attendance.index', [
            'trip' => $trip,
            'attendance' => $attendance,
            'selected_date' => $date,
        ]);
    }
}
