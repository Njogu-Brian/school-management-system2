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
        
        // If teacher has stream assignments, filter by those specific streams
        if (!empty($streamAssignments)) {
            $query->where(function($q) use ($streamAssignments, $assignedClassroomIds) {
                // Students from assigned streams
                foreach ($streamAssignments as $assignment) {
                    $q->orWhere(function($subQ) use ($assignment) {
                        $subQ->where('classroom_id', $assignment->classroom_id)
                             ->where('stream_id', $assignment->stream_id);
                    });
                }
                
                // Also include students from direct classroom assignments (not via streams)
                $directClassroomIds = \Illuminate\Support\Facades\DB::table('classroom_teacher')
                    ->where('teacher_id', $user->id)
                    ->pluck('classroom_id')
                    ->toArray();
                
                $subjectClassroomIds = [];
                if ($user->staff) {
                    $subjectClassroomIds = \Illuminate\Support\Facades\DB::table('classroom_subjects')
                        ->where('staff_id', $user->staff->id)
                        ->distinct()
                        ->pluck('classroom_id')
                        ->toArray();
                }
                
                $nonStreamClassroomIds = array_diff(
                    array_unique(array_merge($directClassroomIds, $subjectClassroomIds)),
                    array_column($streamAssignments, 'classroom_id')
                );
                
                if (!empty($nonStreamClassroomIds)) {
                    $q->orWhereIn('classroom_id', $nonStreamClassroomIds);
                }
            });
        } else {
            // No stream assignments, show all students from assigned classrooms
            $query->whereIn('classroom_id', $assignedClassroomIds);
        }
        
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
        $assignedClassroomIds = $user->getAssignedClassroomIds();
        
        // Verify teacher has access to this student's class
        if (!in_array($student->classroom_id, $assignedClassroomIds)) {
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
