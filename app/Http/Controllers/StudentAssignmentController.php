<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\DropOffPoint;
use App\Models\StudentAssignment;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\TransportFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentAssignmentController extends Controller
{
    public function index()
    {
        $assignments = StudentAssignment::with([
            'student.classroom',
            'student.dropOffPoint',
            'morningTrip.vehicle',
            'eveningTrip.vehicle',
            'morningDropOffPoint',
            'eveningDropOffPoint'
        ])->get();
        return view('student_assignments.index', compact('assignments'));
    }

    public function create()
    {
        $students = Student::where('archive', 0)->where('is_alumni', false)->get();
        $trips = Trip::with('vehicle')->orderBy('trip_name')->get();

        return view('student_assignments.create', compact('students', 'trips'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'morning_trip_id' => 'nullable|exists:trips,id',
            'evening_trip_id' => 'nullable|exists:trips,id',
        ]);
        
        // Check if student already has an assignment
        $exists = StudentAssignment::where('student_id', $request->student_id)->exists();
        
        if ($exists) {
            return redirect()->back()->with('error', 'Student already has a transport assignment. Please edit the existing assignment instead.');
        }
        
        // Get student's drop-off point from transport fee or student record
        $student = Student::find($request->student_id);
        $dropOffPointId = $student->drop_off_point_id;
        
        StudentAssignment::create([
            'student_id' => $request->student_id,
            'morning_trip_id' => $request->morning_trip_id,
            'evening_trip_id' => $request->evening_trip_id,
            // Set drop-off points from student's transport fee record (for backward compatibility)
            'morning_drop_off_point_id' => $dropOffPointId,
            'evening_drop_off_point_id' => $dropOffPointId,
        ]);
        
        return redirect()->route('transport.student-assignments.index')->with('success', 'Student assigned successfully.');
    }

    public function edit(StudentAssignment $student_assignment)
    {
        $students = Student::where('archive', 0)->where('is_alumni', false)->get();
        $trips = Trip::with('vehicle')->orderBy('trip_name')->get();

        return view('student_assignments.edit', compact('student_assignment', 'students', 'trips'));
    }

    public function update(Request $request, StudentAssignment $student_assignment)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'morning_trip_id' => 'nullable|exists:trips,id',
            'evening_trip_id' => 'nullable|exists:trips,id',
        ]);

        // Get student's drop-off point from transport fee or student record
        $student = Student::find($request->student_id);
        $dropOffPointId = $student->drop_off_point_id;

        $student_assignment->update([
            'student_id' => $request->student_id,
            'morning_trip_id' => $request->morning_trip_id,
            'evening_trip_id' => $request->evening_trip_id,
            // Update drop-off points from student's transport fee record (for backward compatibility)
            'morning_drop_off_point_id' => $dropOffPointId,
            'evening_drop_off_point_id' => $dropOffPointId,
        ]);

        return redirect()->route('transport.student-assignments.index')->with('success', 'Assignment updated successfully.');
    }

    public function destroy(StudentAssignment $student_assignment)
    {
        $student_assignment->delete();
        return redirect()->route('transport.student-assignments.index')->with('success', 'Assignment deleted successfully.');
    }

    /**
     * Show bulk assign form
     */
    public function bulkAssign(Request $request)
    {
        $classrooms = Classroom::orderBy('name')->get();
        $streams = Stream::orderBy('name')->get();
        
        $selectedClassroomId = $request->input('classroom_id');
        $selectedStreamId = $request->input('stream_id');
        
        $students = collect();
        
        if ($selectedClassroomId) {
            $query = Student::where('archive', 0)
                ->where('is_alumni', false)
                ->where('classroom_id', $selectedClassroomId)
                ->with(['classroom', 'stream', 'dropOffPoint']);
                
            if ($selectedStreamId) {
                $query->where('stream_id', $selectedStreamId);
            }
            
            // Filter students who have a drop-off point (from transport fee import)
            // Include students with drop_off_point_id or drop_off_point_other
            $query->where(function($q) {
                $q->whereNotNull('drop_off_point_id')
                  ->orWhereNotNull('drop_off_point_other');
            });
            
            $students = $query->orderBy('first_name')->get();
        }
        
        // Get all trips with vehicles for display
        $trips = Trip::with('vehicle')->orderBy('trip_name')->get();
        
        // Group trips by vehicle for display
        $tripsByVehicle = $trips->groupBy('vehicle_id')->map(function ($vehicleTrips, $vehicleId) {
            $vehicle = $vehicleTrips->first()->vehicle;
            return [
                'vehicle' => $vehicle,
                'vehicle_number' => $vehicle ? $vehicle->vehicle_number : 'N/A',
                'trips' => $vehicleTrips,
            ];
        });
        
        // Get existing assignments
        $assignments = $students->isNotEmpty() 
            ? StudentAssignment::whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id')
            : collect();
        
        return view('student_assignments.bulk_assign', compact(
            'classrooms',
            'streams',
            'students',
            'trips',
            'tripsByVehicle',
            'assignments',
            'selectedClassroomId',
            'selectedStreamId'
        ));
    }

    /**
     * Store bulk assignments
     */
    public function bulkAssignStore(Request $request)
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.student_id' => 'required|exists:students,id',
            'assignments.*.morning_trip_id' => 'nullable|exists:trips,id',
            'assignments.*.evening_trip_id' => 'nullable|exists:trips,id',
        ]);
        
        $updated = 0;
        $created = 0;
        
        DB::beginTransaction();
        try {
            foreach ($request->input('assignments', []) as $assignmentData) {
                $studentId = $assignmentData['student_id'];
                $morningTripId = $assignmentData['morning_trip_id'] ?? null;
                $eveningTripId = $assignmentData['evening_trip_id'] ?? null;
                
                // Convert empty string to null for trip IDs
                $morningTripId = $morningTripId === '' ? null : $morningTripId;
                $eveningTripId = $eveningTripId === '' ? null : $eveningTripId;
                
                // Get student's drop-off point from transport fee or student record
                $student = Student::find($studentId);
                $dropOffPointId = $student->drop_off_point_id;
                
                $assignment = StudentAssignment::where('student_id', $studentId)->first();
                
                if ($assignment) {
                    $assignment->update([
                        'morning_trip_id' => $morningTripId,
                        'evening_trip_id' => $eveningTripId,
                        'morning_drop_off_point_id' => $dropOffPointId,
                        'evening_drop_off_point_id' => $dropOffPointId,
                    ]);
                    $updated++;
                } else {
                    // Only create if at least one trip is assigned
                    if ($morningTripId || $eveningTripId) {
                        StudentAssignment::create([
                            'student_id' => $studentId,
                            'morning_trip_id' => $morningTripId,
                            'evening_trip_id' => $eveningTripId,
                            'morning_drop_off_point_id' => $dropOffPointId,
                            'evening_drop_off_point_id' => $dropOffPointId,
                        ]);
                        $created++;
                    }
                }
            }
            
            DB::commit();
            
            $message = "Successfully assigned trips.";
            if ($created > 0) {
                $message .= " {$created} new assignment(s) created.";
            }
            if ($updated > 0) {
                $message .= " {$updated} assignment(s) updated.";
            }
            
            return redirect()->route('transport.student-assignments.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error saving assignments: ' . $e->getMessage());
        }
    }
}
