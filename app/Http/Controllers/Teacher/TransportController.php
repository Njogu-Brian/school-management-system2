<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAssignment;
use App\Models\Vehicle;
use App\Models\Route;
use App\Models\Trip;
use App\Models\DropOffPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransportController extends Controller
{
    /**
     * Display transport information for assigned students
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        
        // Get students from assigned classes who have transport assignments
        $query = Student::whereIn('classroom_id', $assignedClassroomIds)
            ->whereHas('assignments')
            ->with(['classroom', 'assignments.morningTrip', 'assignments.eveningTrip', 
                   'assignments.morningDropOffPoint', 'assignments.eveningDropOffPoint',
                   'vehicle', 'route']);
        
        // Filter by classroom if selected
        if ($request->filled('classroom_id') && in_array($request->classroom_id, $assignedClassroomIds)) {
            $query->where('classroom_id', $request->classroom_id);
        }
        
        // Filter by route if selected
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }
        
        $students = $query->orderBy('first_name')->paginate(30);
        $classrooms = \App\Models\Academics\Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
        $routes = Route::orderBy('name')->get();
        
        return view('teacher.transport.index', compact('students', 'classrooms', 'routes'));
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
            'assignments.morningTrip.vehicle',
            'assignments.eveningTrip.vehicle',
            'assignments.morningDropOffPoint.route',
            'assignments.eveningDropOffPoint.route',
            'vehicle',
            'route.vehicles',
            'dropOffPoint.route'
        ]);
        
        return view('teacher.transport.show', compact('student'));
    }
}
