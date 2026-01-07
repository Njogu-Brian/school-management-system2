<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\StudentAssignment;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;
use Barryvdh\DomPDF\Facade\Pdf;

class DailyTransportListController extends Controller
{
    /**
     * Show the daily transport list page
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $vehicleId = $request->input('vehicle_id');
        $classroomId = $request->input('classroom_id');

        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $classrooms = Classroom::orderBy('name')->get();

        // Get present students for the selected date
        $presentStudentIds = Attendance::whereDate('date', $date)
            ->where('status', 'present')
            ->pluck('student_id')
            ->toArray();

        // Build query for students with transport assignments
        $query = Student::whereIn('id', $presentStudentIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereHas('assignments', function($q) {
                $q->whereNotNull('evening_trip_id');
            })
            ->with([
                'classroom',
                'assignments.eveningTrip.vehicle',
                'assignments.eveningDropOffPoint'
            ]);

        if ($vehicleId) {
            $query->whereHas('assignments.eveningTrip', function($q) use ($vehicleId) {
                $q->where('vehicle_id', $vehicleId);
            });
        }

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        $students = $query->orderBy('first_name')->get();

        // Group students by vehicle
        $studentsByVehicle = $students->groupBy(function($student) {
            return $student->assignments->first()?->eveningTrip?->vehicle?->vehicle_number ?? 'Unknown';
        });

        return view('transport.daily-list.index', compact(
            'students',
            'studentsByVehicle',
            'vehicles',
            'classrooms',
            'date',
            'vehicleId',
            'classroomId'
        ));
    }

    /**
     * Download transport list as Excel
     */
    public function downloadExcel(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $vehicleId = $request->input('vehicle_id');
        $classroomId = $request->input('classroom_id');

        $exportData = $this->getTransportListData($date, $vehicleId, $classroomId);

        $filename = 'transport_list_' . $date . '.xlsx';
        return Excel::download(new ArrayExport($exportData['data'], $exportData['headers']), $filename);
    }

    /**
     * Print transport list (PDF)
     */
    public function printList(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $vehicleId = $request->input('vehicle_id');
        $classroomId = $request->input('classroom_id');

        // Get present students
        $presentStudentIds = Attendance::whereDate('date', $date)
            ->where('status', 'present')
            ->pluck('student_id')
            ->toArray();

        $query = Student::whereIn('id', $presentStudentIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereHas('assignments', function($q) {
                $q->whereNotNull('evening_trip_id');
            })
            ->with([
                'classroom',
                'assignments.eveningTrip.vehicle',
                'assignments.eveningDropOffPoint'
            ]);

        if ($vehicleId) {
            $query->whereHas('assignments.eveningTrip', function($q) use ($vehicleId) {
                $q->where('vehicle_id', $vehicleId);
            });
        }

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        $students = $query->orderBy('first_name')->get();

        // Group by vehicle
        $studentsByVehicle = $students->groupBy(function($student) {
            return $student->assignments->first()?->eveningTrip?->vehicle?->vehicle_number ?? 'Unknown';
        });

        $pdf = Pdf::loadView('transport.daily-list.print', [
            'studentsByVehicle' => $studentsByVehicle,
            'date' => $date,
            'vehicleId' => $vehicleId,
            'classroomId' => $classroomId
        ]);

        return $pdf->stream('transport_list_' . $date . '.pdf');
    }

    /**
     * Print vehicle-specific list
     */
    public function printVehicle(Request $request, $vehicleId)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $vehicle = Vehicle::findOrFail($vehicleId);

        // Get present students for this vehicle
        $presentStudentIds = Attendance::whereDate('date', $date)
            ->where('status', 'present')
            ->pluck('student_id')
            ->toArray();

        $students = Student::whereIn('id', $presentStudentIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereHas('assignments.eveningTrip', function($q) use ($vehicleId) {
                $q->where('vehicle_id', $vehicleId);
            })
            ->with([
                'classroom',
                'assignments.eveningTrip',
                'assignments.eveningDropOffPoint'
            ])
            ->orderBy('first_name')
            ->get();

        // Group by trip
        $studentsByTrip = $students->groupBy(function($student) {
            return $student->assignments->first()?->eveningTrip?->trip_name ?? 'Unknown';
        });

        $pdf = Pdf::loadView('transport.daily-list.print-vehicle', [
            'vehicle' => $vehicle,
            'studentsByTrip' => $studentsByTrip,
            'date' => $date,
            'totalStudents' => $students->count()
        ]);

        return $pdf->stream('transport_list_' . $vehicle->vehicle_number . '_' . $date . '.pdf');
    }

    /**
     * Get transport list data for export
     */
    protected function getTransportListData($date, $vehicleId = null, $classroomId = null)
    {
        $presentStudentIds = Attendance::whereDate('date', $date)
            ->where('status', 'present')
            ->pluck('student_id')
            ->toArray();

        $query = Student::whereIn('id', $presentStudentIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereHas('assignments', function($q) {
                $q->whereNotNull('evening_trip_id');
            })
            ->with([
                'classroom',
                'assignments.eveningTrip.vehicle',
                'assignments.eveningDropOffPoint'
            ]);

        if ($vehicleId) {
            $query->whereHas('assignments.eveningTrip', function($q) use ($vehicleId) {
                $q->where('vehicle_id', $vehicleId);
            });
        }

        if ($classroomId) {
            $query->where('classroom_id', $classroomId);
        }

        $students = $query->orderBy('first_name')->get();

        $headers = ['Admission No', 'Student Name', 'Class', 'Vehicle', 'Trip', 'Drop-off Point'];
        $data = [];

        foreach ($students as $student) {
            $assignment = $student->assignments->first();
            $data[] = [
                $student->admission_number,
                $student->full_name,
                $student->classroom?->name ?? 'N/A',
                $assignment?->eveningTrip?->vehicle?->vehicle_number ?? 'N/A',
                $assignment?->eveningTrip?->trip_name ?? 'N/A',
                $assignment?->eveningDropOffPoint?->name ?? 'N/A'
            ];
        }

        return ['headers' => $headers, 'data' => $data];
    }
}

