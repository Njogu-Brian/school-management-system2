<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Assessment;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Student;
use App\Models\Staff;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $assessments = Assessment::with(['classroom', 'subject', 'student', 'staff'])
            ->when($request->filled('week_ending'), function ($q) use ($request) {
                $q->whereDate('week_ending', $request->week_ending);
            })
            ->orderByDesc('assessment_date')
            ->limit(200)
            ->get();

        return view('academics.assessments.index', compact('assessments'));
    }

    public function create()
    {
        $classrooms = Classroom::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $students = Student::orderBy('first_name')->get();
        $staff = Staff::orderBy('first_name')->get();

        return view('academics.assessments.create', compact('classrooms', 'subjects', 'students', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'assessment_date' => 'nullable|date',
            'week_ending' => 'nullable|date',
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'student_id' => 'required|exists:students,id',
            'staff_id' => 'nullable|exists:staff,id',
            'assessment_type' => 'nullable|string|max:50',
            'score' => 'nullable|numeric|min:0',
            'out_of' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:255',
            'academic_group' => 'nullable|string|max:100',
        ]);

        Assessment::create($data);

        return redirect()->route('academics.assessments.index')
            ->with('success', 'Assessment saved successfully.');
    }
}
