<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\StudentBehaviour;
use App\Models\Academics\Behaviour;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentBehaviourController extends Controller
{
    public function index()
    {
        $query = StudentBehaviour::with(['student.classroom','behaviour','teacher','term','academicYear']);
        
        // Filter by teacher/senior teacher assigned classes
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher) {
            $classroomIds = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            
            if (!empty($classroomIds)) {
                $query->whereHas('student', function($q) use ($classroomIds) {
                    $q->where('archive', 0)->where('is_alumni', false);
                    $q->whereIn('classroom_id', $classroomIds);
                });
            } else {
                $query->whereRaw('1 = 0'); // No access
            }
        }
        
        $records = $query->latest()->paginate(30);

        return view('academics.student_behaviours.index', compact('records'));
    }

    public function create()
    {
        $studentsQuery = Student::with('classroom')->orderBy('last_name')->orderBy('first_name');
        
        // Filter students by teacher/senior teacher assigned classes
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') || $user->hasRole('teacher') || $user->hasRole('Senior Teacher');
        if ($isTeacher) {
            $classroomIds = array_unique(array_merge(
                $user->getAssignedClassroomIds(),
                $user->getSupervisedClassroomIds()
            ));
            
            if (!empty($classroomIds)) {
                $studentsQuery->whereIn('classroom_id', $classroomIds);
            } else {
                $studentsQuery->whereRaw('1 = 0'); // No access
            }
        }
        
        return view('academics.student_behaviours.create', [
            'students' => $studentsQuery->get(),
            'behaviours' => Behaviour::orderBy('name')->get(),
            'years' => AcademicYear::orderByDesc('year')->get(),
            'terms' => Term::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'behaviour_id' => 'required|exists:behaviours,id',
            'term_id' => 'required|exists:terms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'notes' => 'nullable|string|max:500',
        ]);

        // Validate student is not alumni or archived
        $student = Student::withAlumni()->findOrFail($data['student_id']);
        if ($student->is_alumni || $student->archive) {
            return back()
                ->withInput()
                ->with('error', 'Cannot record behavior for alumni or archived students.');
        }
        
        // Validate teacher has access to student's class
        if (Auth::user()->hasRole('Teacher')) {
            $assignedClassroomIds = Auth::user()->getAssignedClassroomIds();
            
            if (!in_array($student->classroom_id, $assignedClassroomIds)) {
                return back()
                    ->withInput()
                    ->with('error', 'You do not have access to record behavior for students in this class.');
            }
        }
        
        $staff = Auth::user()->staff;
        StudentBehaviour::create($data + [
            'recorded_by' => $staff ? $staff->id : null
        ]);

        return redirect()->route('academics.student-behaviours.index')
            ->with('success', 'behaviour record saved.');
    }

    public function destroy(StudentBehaviour $student_behaviour)
    {
        // Validate teacher has access to student's class
        if (Auth::user()->hasRole('Teacher')) {
            $assignedClassroomIds = Auth::user()->getAssignedClassroomIds();
            
            if (!in_array($student_behaviour->student->classroom_id, $assignedClassroomIds)) {
                abort(403, 'You do not have access to delete behavior records for students in this class.');
            }
        }
        
        $student_behaviour->delete();
        return back()->with('success','behaviour record deleted.');
    }
}
