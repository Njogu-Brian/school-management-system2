<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\TransportFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TripController extends Controller
{
    public function index()
    {
        $trips = Trip::with(['vehicle', 'driver.user'])->orderBy('trip_name')->get();
        return view('trips.index', compact('trips'));
    }

    public function create()
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        return view('trips.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'name' => 'required|string|max:255',
            'day_of_week' => 'nullable|array',
            'day_of_week.*' => 'integer|in:1,2,3,4,5,6,7',
        ]);

        $data = $request->only(['vehicle_id', 'driver_id', 'direction']);
        $data['trip_name'] = $request->input('name');

        $dayOfWeek = $request->input('day_of_week');
        if (is_array($dayOfWeek) && !empty($dayOfWeek)) {
            $data['day_of_week'] = array_map('intval', $dayOfWeek);
        } else {
            $data['day_of_week'] = null;
        }

        Trip::create($data);
        return redirect()->route('transport.trips.index')->with('success', 'Trip created successfully.');
    }

    public function edit(Trip $trip)
    {
        $vehicles = Vehicle::orderBy('vehicle_number')->get();
        $trip->load(['vehicle', 'driver.user']);
        return view('trips.edit', compact('trip', 'vehicles'));
    }

    public function update(Request $request, Trip $trip)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'day_of_week' => 'nullable|array',
            'day_of_week.*' => 'integer|in:1,2,3,4,5,6,7',
        ]);

        $data = [
            'trip_name' => $request->input('name'),
            'vehicle_id' => $request->input('vehicle_id') ?: null,
            'driver_id' => $request->input('driver_id') ?: null,
            'direction' => $request->input('direction') ?: null,
        ];

        $dayOfWeek = $request->input('day_of_week');
        if (is_array($dayOfWeek) && !empty($dayOfWeek)) {
            $data['day_of_week'] = array_map('intval', $dayOfWeek);
        } else {
            $data['day_of_week'] = null;
        }

        $trip->update($data);

        return redirect()->route('transport.trips.index')->with('success', 'Trip updated successfully!');
    }

    public function destroy(Trip $trip)
    {
        try {
            $detached = 0;

            DB::transaction(function () use ($trip, &$detached) {
                $detached = $trip->detachStudentAssignments();
                $trip->delete();
            });

            $message = 'Trip deleted successfully.';
            if ($detached > 0) {
                $message .= " Cleared {$detached} student assignment reference(s).";
            }

            return redirect()->route('transport.trips.index')->with('success', $message);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('transport.trips.index')
                ->with('error', 'Could not delete trip: ' . $e->getMessage());
        }
    }

    /**
     * Open a trip to search and bulk-assign students.
     */
    public function assign(Trip $trip)
    {
        $trip->load(['vehicle', 'driver.user']);
        $leg = $trip->assignmentLeg() ?? 'morning';

        $assigned = StudentAssignment::query()
            ->with(['student.classroom', 'student.stream'])
            ->where(function ($q) use ($trip) {
                $q->where('morning_trip_id', $trip->id)
                    ->orWhere('evening_trip_id', $trip->id);
                if (Schema::hasColumn('student_assignments', 'trip_id')) {
                    $q->orWhere('trip_id', $trip->id);
                }
            })
            ->get()
            ->filter(fn ($a) => $a->student)
            ->values();

        return view('trips.assign', [
            'trip' => $trip,
            'assigned' => $assigned,
            'defaultLeg' => $leg,
        ]);
    }

    /**
     * Save bulk student assignments onto this trip.
     */
    public function assignStore(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,id',
            'leg' => 'required|in:morning,evening',
        ]);

        $leg = $validated['leg'];
        $column = $leg === 'morning' ? 'morning_trip_id' : 'evening_trip_id';
        $studentIds = array_map('intval', $validated['student_ids']);
        $assigned = 0;

        DB::transaction(function () use ($studentIds, $column, $trip, &$assigned) {
            foreach ($studentIds as $studentId) {
                $student = Student::withoutGlobalScope('active')->find($studentId);
                if (!$student) {
                    continue;
                }

                $assignment = StudentAssignment::firstOrNew(['student_id' => $studentId]);
                $assignment->{$column} = $trip->id;

                if (!$assignment->morning_drop_off_point_id && $student->drop_off_point_id) {
                    $assignment->morning_drop_off_point_id = $student->drop_off_point_id;
                }
                if (!$assignment->evening_drop_off_point_id && $student->drop_off_point_id) {
                    $assignment->evening_drop_off_point_id = $student->drop_off_point_id;
                }

                $assignment->save();
                $assigned++;

                TransportFeeService::recalculateForStudent(
                    $studentId,
                    null,
                    null,
                    true,
                    'calculated',
                    'Recalculated after trip student assign'
                );
            }
        });

        return redirect()
            ->route('transport.trips.assign', $trip)
            ->with('success', "Assigned {$assigned} student(s) to {$trip->trip_name}. Run Post Pending Fees if transport list prices changed.");
    }

    /**
     * Remove a student from this trip.
     */
    public function unassign(Request $request, Trip $trip, Student $student)
    {
        $assignment = StudentAssignment::where('student_id', $student->id)->first();
        if ($assignment) {
            if ($assignment->morning_trip_id == $trip->id) {
                $assignment->morning_trip_id = null;
            }
            if ($assignment->evening_trip_id == $trip->id) {
                $assignment->evening_trip_id = null;
            }
            if (Schema::hasColumn('student_assignments', 'trip_id') && $assignment->trip_id == $trip->id) {
                $assignment->trip_id = null;
            }
            $assignment->save();

            TransportFeeService::recalculateForStudent(
                $student->id,
                null,
                null,
                true,
                'calculated',
                'Recalculated after trip student unassign'
            );
        }

        return redirect()
            ->route('transport.trips.assign', $trip)
            ->with('success', 'Student removed from this trip.');
    }
}
