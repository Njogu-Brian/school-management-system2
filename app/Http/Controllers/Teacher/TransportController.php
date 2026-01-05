<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\Vehicle;
use App\Models\Trip;
use App\Models\DropOffPoint;
use App\Services\TransportAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TransportController extends Controller
{
    /**
     * Display transport information for assigned students
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        
        // Get students from assigned classes who have transport assignments (exclude alumni)
        $query = Student::whereIn('classroom_id', $assignedClassroomIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereHas('assignments')
            ->with([
                'classroom', 
                'assignments.morningTrip.vehicle.driver',
                'assignments.morningTrip.driver',
                'assignments.eveningTrip.vehicle.driver',
                'assignments.eveningTrip.driver',
                'assignments.morningDropOffPoint',
                'assignments.eveningDropOffPoint',
                'vehicle'
            ]);
        
        // Filter by classroom if selected
        if ($request->filled('classroom_id') && in_array($request->classroom_id, $assignedClassroomIds)) {
            $query->where('classroom_id', $request->classroom_id);
        }
        
        $students = $query->orderBy('first_name')->paginate(30);
        $classrooms = \App\Models\Academics\Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
        
        return view('teacher.transport.index', compact('students', 'classrooms'));
    }
    
    /**
     * Show detailed transport info for a specific student
     */
    public function show(Student $student)
    {
        $user = Auth::user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        
        // Verify teacher has access to this student's class
        if (!in_array($student->classroom_id, $assignedClassroomIds)) {
            abort(403, 'You do not have access to view transport information for this student.');
        }
        
        $student->load([
            'classroom',
            'assignments.morningTrip.vehicle.driver',
            'assignments.morningTrip.driver',
            'assignments.eveningTrip.vehicle.driver',
            'assignments.eveningTrip.driver',
            'assignments.morningDropOffPoint',
            'assignments.eveningDropOffPoint',
            'vehicle',
            'dropOffPoint'
        ]);
        
        return view('teacher.transport.show', compact('student'));
    }

    /**
     * Generate printable transport sheet (daily or weekly)
     */
    public function transportSheet(Request $request)
    {
        $user = Auth::user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        
        $type = $request->get('type', 'daily'); // daily or weekly
        $date = $request->get('date', now()->toDateString());
        $classroomId = $request->get('classroom_id');
        
        // Verify classroom access
        if ($classroomId && !in_array($classroomId, $assignedClassroomIds)) {
            abort(403, 'You do not have access to this classroom.');
        }
        
        $classroomIds = $classroomId ? [$classroomId] : $assignedClassroomIds;
        
        // Get students with transport assignments
        $students = Student::whereIn('classroom_id', $classroomIds)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->whereHas('assignments')
            ->with([
                'classroom',
                'assignments.morningTrip.vehicle',
                'assignments.morningTrip.driver',
                'assignments.morningTrip.stops',
                'assignments.eveningTrip.vehicle',
                'assignments.eveningTrip.driver',
                'assignments.eveningTrip.stops',
                'assignments.morningDropOffPoint',
                'assignments.eveningDropOffPoint'
            ])
            ->orderBy('first_name')
            ->get();
        
        // Resolve assignments using TransportAssignmentService
        $assignmentService = app(TransportAssignmentService::class);
        $dateCarbon = Carbon::parse($date);
        
        $transportData = [];
        foreach ($students as $student) {
            $morningAssignment = $assignmentService->resolveAssignment($student, $dateCarbon, 'pickup');
            $eveningAssignment = $assignmentService->resolveAssignment($student, $dateCarbon, 'dropoff');
            
            $transportData[] = [
                'student' => $student,
                'morning' => $morningAssignment,
                'evening' => $eveningAssignment,
            ];
        }
        
        $classroom = $classroomId ? \App\Models\Academics\Classroom::find($classroomId) : null;
        $classrooms = \App\Models\Academics\Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
        
        return view('teacher.transport.sheet', compact(
            'transportData', 
            'type', 
            'date', 
            'classroom', 
            'classrooms',
            'classroomId'
        ));
    }
}
