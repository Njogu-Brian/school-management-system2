<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\StudentFollowup;
use App\Models\Student;
use App\Models\Academics\Classroom;
use Illuminate\Http\Request;

class StudentFollowupController extends Controller
{
    public function index(Request $request)
    {
        $reports = StudentFollowup::with(['student', 'classroom'])
            ->when($request->filled('week_ending'), function ($q) use ($request) {
                $q->whereDate('week_ending', $request->week_ending);
            })
            ->orderByDesc('week_ending')
            ->limit(200)
            ->get();

        return view('reports.student_followups.index', compact('reports'));
    }

    public function create()
    {
        $students = Student::orderBy('first_name')->get();
        $classrooms = Classroom::orderBy('name')->get();

        return view('reports.student_followups.create', compact('students', 'classrooms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'week_ending' => 'required|date',
            'campus' => 'nullable|in:lower,upper',
            'student_id' => 'required|exists:students,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'academic_concern' => 'nullable|boolean',
            'behavior_concern' => 'nullable|boolean',
            'action_taken' => 'nullable|string',
            'parent_contacted' => 'nullable|boolean',
            'progress_status' => 'nullable|in:Improving,Same,Worse',
            'notes' => 'nullable|string',
        ]);

        StudentFollowup::create($data);

        return redirect()->route('reports.student-followups.index')
            ->with('success', 'Student follow-up saved successfully.');
    }
}
