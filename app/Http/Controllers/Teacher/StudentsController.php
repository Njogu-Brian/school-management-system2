<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentsController extends Controller
{
    /**
     * Display students assigned to teacher's classes
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        $streamAssignments = $user->getStreamAssignments();
        
        // Build query - if teacher is assigned to specific streams, only show students from those streams
        $query = Student::with(['classroom', 'stream', 'parent']);
        
        // Apply teacher-specific filtering
        $user->applyTeacherStudentFilter($query, $streamAssignments, $assignedClassroomIds);
        
        // Filter by classroom
        if ($request->filled('classroom_id') && in_array($request->classroom_id, $assignedClassroomIds)) {
            $query->where('classroom_id', $request->classroom_id);
            
            // If teacher has stream assignments for this classroom, filter by those streams
            $classroomStreamAssignments = array_filter($streamAssignments, function($a) use ($request) {
                return $a->classroom_id == $request->classroom_id;
            });
            if (!empty($classroomStreamAssignments)) {
                $streamIds = array_column($classroomStreamAssignments, 'stream_id');
                $query->whereIn('stream_id', $streamIds);
            }
        }
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }
        
        $students = $query->orderBy('first_name')->paginate(30);
        $classrooms = Classroom::whereIn('id', $assignedClassroomIds)->orderBy('name')->get();
        
        return view('teacher.students.index', compact('students', 'classrooms'));
    }
    
    /**
     * Show detailed student information
     */
    public function show(Student $student)
    {
        $user = Auth::user();
        $streamAssignments = $user->getStreamAssignments();
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        
        // Verify teacher has access to this student
        $hasAccess = false;
        if (!empty($streamAssignments)) {
            // Check if student belongs to any assigned stream
            foreach ($streamAssignments as $assignment) {
                if ($student->classroom_id == $assignment->classroom_id && 
                    $student->stream_id == $assignment->stream_id) {
                    $hasAccess = true;
                    break;
                }
            }
            // Also check direct classroom assignments (not via streams)
            if (!$hasAccess && in_array($student->classroom_id, $assignedClassroomIds)) {
                // Check if this classroom is not part of stream assignments
                $streamClassroomIds = array_column($streamAssignments, 'classroom_id');
                if (!in_array($student->classroom_id, $streamClassroomIds)) {
                    $hasAccess = true;
                }
            }
        } else {
            // No stream assignments, check classroom access
            $hasAccess = in_array($student->classroom_id, $assignedClassroomIds);
        }
        
        if (!$hasAccess) {
            abort(403, 'You do not have access to view this student.');
        }
        
        $student->load([
            'classroom',
            'stream',
            'parent',
            'category',
            'attendances' => function($q) {
                $q->latest()->take(10);
            },
            'assignments.morningTrip',
            'assignments.eveningTrip',
            'assignments.morningDropOffPoint',
            'assignments.eveningDropOffPoint',
            'vehicle',
            'route'
        ]);
        
        // Get recent exam marks
        $recentMarks = \App\Models\Academics\ExamMark::where('student_id', $student->id)
            ->with(['exam', 'subject'])
            ->latest()
            ->take(10)
            ->get();
        
        // Get recent homework submissions
        $recentHomework = \App\Models\Academics\HomeworkDiary::where('student_id', $student->id)
            ->with(['homework.subject'])
            ->latest()
            ->take(10)
            ->get();
        
        return view('teacher.students.show', compact('student', 'recentMarks', 'recentHomework'));
    }
}
