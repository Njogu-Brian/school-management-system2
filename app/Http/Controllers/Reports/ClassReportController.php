<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Reports\ClassReport;
use App\Models\Academics\Classroom;
use App\Models\Staff;
use Illuminate\Http\Request;

class ClassReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = ClassReport::with(['classroom', 'classTeacher'])
            ->when($request->filled('week_ending'), function ($q) use ($request) {
                $q->whereDate('week_ending', $request->week_ending);
            })
            ->orderByDesc('week_ending')
            ->limit(200)
            ->get();

        return view('reports.class_reports.index', compact('reports'));
    }

    public function create()
    {
        $classrooms = Classroom::orderBy('name')->get();
        $staff = Staff::orderBy('first_name')->get();

        return view('reports.class_reports.create', compact('classrooms', 'staff'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'week_ending' => 'required|date',
            'campus' => 'nullable|in:lower,upper',
            'classroom_id' => 'required|exists:classrooms,id',
            'class_teacher_id' => 'nullable|exists:staff,id',
            'total_learners' => 'nullable|integer|min:0',
            'frequent_absentees' => 'nullable|integer|min:0',
            'discipline_level' => 'nullable|in:Excellent,Good,Fair,Poor',
            'homework_completion' => 'nullable|in:High,Medium,Low',
            'learners_struggling' => 'nullable|integer|min:0',
            'learners_improved' => 'nullable|integer|min:0',
            'parents_to_contact' => 'nullable|integer|min:0',
            'classroom_condition' => 'nullable|in:Good,Fair,Poor',
            'notes' => 'nullable|string',
            'academic_group' => 'nullable|string|max:100',
        ]);

        ClassReport::create($data);

        return redirect()->route('reports.class-reports.index')
            ->with('success', 'Class report saved successfully.');
    }
}
