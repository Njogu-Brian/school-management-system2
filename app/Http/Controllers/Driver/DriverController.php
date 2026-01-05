<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Staff;
use App\Services\TransportAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DriverController extends Controller
{
    protected $assignmentService;

    public function __construct(TransportAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
        $this->middleware('role:Driver');
    }

    /**
     * Driver dashboard - shows today's trips
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;

        if (!$staff) {
            return redirect()->back()->with('error', 'Staff profile not found.');
        }

        $date = $request->get('date', now()->toDateString());
        $carbonDate = Carbon::parse($date);

        // Get trips assigned to this driver for the selected date
        $trips = Trip::where('driver_id', $staff->id)
            ->where(function ($q) use ($carbonDate) {
                $dayOfWeek = $carbonDate->dayOfWeek;
                $dayOfWeekAdjusted = $dayOfWeek === 0 ? 7 : $dayOfWeek;
                
                $q->whereNull('day_of_week') // All days
                  ->orWhere('day_of_week', $dayOfWeekAdjusted);
            })
            ->with(['vehicle', 'stops.dropOffPoint'])
            ->get();

        // Get students for each trip
        $tripsWithStudents = $trips->map(function ($trip) use ($carbonDate) {
            $students = $this->assignmentService->getStudentsForTrip($trip, $carbonDate);
            
            // Group students by class
            $studentsByClass = $students->groupBy(function ($student) {
                return $student->classroom->name ?? 'Unassigned';
            });

            return [
                'trip' => $trip,
                'students' => $students,
                'students_by_class' => $studentsByClass,
                'student_count' => $students->count(),
            ];
        });

        return view('driver.index', [
            'trips' => $tripsWithStudents,
            'selected_date' => $date,
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * Show trip details with students
     */
    public function showTrip(Trip $trip, Request $request)
    {
        $user = Auth::user();
        $staff = $user->staff;

        if ($trip->driver_id !== $staff->id) {
            return redirect()->route('driver.index')->with('error', 'You are not assigned to this trip.');
        }

        $date = $request->get('date', now()->toDateString());
        $carbonDate = Carbon::parse($date);

        $students = $this->assignmentService->getStudentsForTrip($trip, $carbonDate);
        
        // Group by class, sorted alphabetically within each class
        $studentsByClass = $students->sortBy('first_name')
            ->groupBy(function ($student) {
                return $student->classroom->name ?? 'Unassigned';
            });

        $trip->load(['vehicle', 'stops.dropOffPoint', 'driver']);

        return view('driver.trip', [
            'trip' => $trip,
            'students' => $students,
            'students_by_class' => $studentsByClass,
            'selected_date' => $date,
        ]);
    }

    /**
     * Generate printable transport sheet for driver (trip-specific)
     */
    public function transportSheet(Request $request, Trip $trip = null)
    {
        $user = Auth::user();
        $staff = $user->staff;

        if (!$staff) {
            abort(403, 'Staff profile not found.');
        }

        $type = $request->get('type', 'daily'); // daily or weekly
        $date = $request->get('date', now()->toDateString());
        $carbonDate = Carbon::parse($date);

        // If trip specified, verify driver is assigned
        if ($trip) {
            if ($trip->driver_id !== $staff->id) {
                abort(403, 'You are not assigned to this trip.');
            }
            $trips = collect([$trip]);
        } else {
            // Get all trips assigned to this driver for the date
            $trips = Trip::where('driver_id', $staff->id)
                ->where(function ($q) use ($carbonDate) {
                    $dayOfWeek = $carbonDate->dayOfWeek;
                    $dayOfWeekAdjusted = $dayOfWeek === 0 ? 7 : $dayOfWeek;
                    
                    $q->whereNull('day_of_week')
                      ->orWhere('day_of_week', $dayOfWeekAdjusted);
                })
                ->with(['vehicle', 'stops.dropOffPoint', 'driver'])
                ->get();
        }

        // Get students for each trip
        $transportData = [];
        foreach ($trips as $tripItem) {
            $students = $this->assignmentService->getStudentsForTrip($tripItem, $carbonDate);
            
            $transportData[] = [
                'trip' => $tripItem,
                'students' => $students->sortBy('first_name'),
            ];
        }

        return view('driver.transport-sheet', compact(
            'transportData',
            'type',
            'date',
            'staff'
        ));
    }
}
