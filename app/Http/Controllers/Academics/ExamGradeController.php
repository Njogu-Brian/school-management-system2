<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamGrade;
use Illuminate\Http\Request;

class ExamGradeController extends Controller
{
    public function index()
    {
        $grades = ExamGrade::orderBy('exam_type')->orderByDesc('percent_from')->get();
        return view('academics.exam_grades.index', compact('grades'));
    }

    public function create()
    {
        return view('academics.exam_grades.create');
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'exam_type'    => 'required|string|max:50',
            'grade_name'   => 'required|string|max:10',
            'percent_from' => 'required|numeric|min:0|max:100',
            'percent_upto' => 'required|numeric|min:0|max:100',
            'grade_point'  => 'nullable|numeric|min:0|max:12',
            'description'  => 'nullable|string|max:255',
        ]);

        ExamGrade::create($v);
        return redirect()->route('academics.exam-grades.index')->with('success','Grade added.');
    }

    public function edit(ExamGrade $exam_grade)
    {
        return view('academics.exam_grades.edit', compact('exam_grade'));
    }

    public function update(Request $request, ExamGrade $exam_grade)
    {
        $v = $request->validate([
            'exam_type'    => 'required|string|max:50',
            'grade_name'   => 'required|string|max:10',
            'percent_from' => 'required|numeric|min:0|max:100',
            'percent_upto' => 'required|numeric|min:0|max:100',
            'grade_point'  => 'nullable|numeric|min:0|max:12',
            'description'  => 'nullable|string|max:255',
        ]);

        $exam_grade->update($v);
        return redirect()->route('academics.exam-grades.index')->with('success','Grade updated.');
    }

    public function destroy(ExamGrade $exam_grade)
    {
        $exam_grade->delete();
        return back()->with('success','Grade deleted.');
    }
}
