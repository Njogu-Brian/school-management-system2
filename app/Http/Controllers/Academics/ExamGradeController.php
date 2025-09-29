<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamGrade;
use Illuminate\Http\Request;

class ExamGradeController extends Controller
{
    public function index()
    {
        $grades = ExamGrade::orderBy('exam_type')->orderBy('percent_from')->get();
        return view('academics.exam_grades.index', compact('grades'));
    }

    public function create()
    {
        return view('academics.exam_grades.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_type'     => 'required|string|max:100',
            'grade_name'    => 'required|string|max:50',
            'percent_from'  => 'required|numeric|min:0|max:100',
            'percent_upto'  => 'required|numeric|min:0|max:100|gte:percent_from',
            'grade_point'   => 'nullable|numeric|min:0|max:10',
            'description'   => 'nullable|string|max:255',
        ]);

        ExamGrade::create($request->all());
        return redirect()->route('academics.exam-grades.index')->with('success','Grade added successfully.');
    }

    public function edit(ExamGrade $exam_grade)
    {
        return view('academics.exam_grades.edit', compact('exam_grade'));
    }

    public function update(Request $request, ExamGrade $exam_grade)
    {
        $request->validate([
            'exam_type'     => 'required|string|max:100',
            'grade_name'    => 'required|string|max:50',
            'percent_from'  => 'required|numeric|min:0|max:100',
            'percent_upto'  => 'required|numeric|min:0|max:100|gte:percent_from',
            'grade_point'   => 'nullable|numeric|min:0|max:10',
            'description'   => 'nullable|string|max:255',
        ]);

        $exam_grade->update($request->all());
        return redirect()->route('academics.exam-grades.index')->with('success','Grade updated successfully.');
    }

    public function destroy(ExamGrade $exam_grade)
    {
        $exam_grade->delete();
        return redirect()->route('academics.exam-grades.index')->with('success','Grade deleted.');
    }
}
