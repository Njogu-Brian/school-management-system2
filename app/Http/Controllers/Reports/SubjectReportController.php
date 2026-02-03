<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\SubjectReport;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Staff;
use Illuminate\Http\Request;

class SubjectReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = SubjectReport::with(['classroom', 'subject', 'staff'])
            ->when($request->filled('week_ending'), function ($q) use ($request) {
                $q->whereDate('week_ending', $request->week_ending);
            })
            ->orderByDesc('week_ending')
            ->limit(200)
            ->get();

        return view('reports.subject_reports.index', compact('reports'));
    }

    public function create()
    {
        $classrooms = Classroom::orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $staff = Staff::orderBy('first_name')->get();

        return view('reports.subject_reports.create', compact('classrooms', 'subjects', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'week_ending' => 'required|date',
            'campus' => 'nullable|in:lower,upper',
            'subject_id' => 'required|exists:subjects,id',
            'staff_id' => 'nullable|exists:staff,id',
            'classroom_id' => 'required|exists:classrooms,id',
            'topics_covered' => 'nullable|string',
            'syllabus_status' => 'nullable|in:On Track,Slightly Behind,Behind',
            'strong_percent' => 'nullable|numeric|min:0|max:100',
            'average_percent' => 'nullable|numeric|min:0|max:100',
            'struggling_percent' => 'nullable|numeric|min:0|max:100',
            'homework_given' => 'nullable|boolean',
            'test_done' => 'nullable|boolean',
            'marking_done' => 'nullable|boolean',
            'main_challenge' => 'nullable|string|max:255',
            'support_needed' => 'nullable|string|max:255',
            'academic_group' => 'nullable|string|max:100',
        ]);

        SubjectReport::create($data);

        return redirect()->route('reports.subject-reports.index')
            ->with('success', 'Subject report saved successfully.');
    }
}
