<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\ExamGroup;
use App\Models\Academics\ExamType;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamGroupController extends Controller
{
    public function index()
    {
        $groups = ExamGroup::with(['type'])
            ->withCount('exams')
            ->orderByDesc('id')
            ->paginate(20);

        return view('academics.exam_groups.index', [
            'groups' => $groups,
            'types'  => ExamType::orderBy('name')->get(),
            'years'  => AcademicYear::orderByDesc('year')->get(),
            'terms'  => Term::orderBy('name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'exam_type_id' => 'required|exists:exam_types,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        ExamGroup::create($data + ['created_by' => Auth::id()]);

        return back()->with('success','Exam group created.');
    }

    public function edit(ExamGroup $group)
    {
        return view('academics.exam_groups.edit', [
            'group'=>$group,
            'types'=>ExamType::orderBy('name')->get(),
            'years'=>AcademicYear::orderByDesc('year')->get(),
            'terms'=>Term::orderBy('name')->get(),
        ]);
    }

    public function update(Request $r, ExamGroup $group)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255',
            'exam_type_id' => 'required|exists:exam_types,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:terms,id',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);
        $group->update($data);

        return redirect()->route('exams.groups.index')->with('success','Exam group updated.');
    }

    public function destroy(ExamGroup $group)
    {
        $group->delete();
        return back()->with('success','Exam group deleted.');
    }
}
