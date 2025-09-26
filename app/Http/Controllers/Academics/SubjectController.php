<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Subject;
use App\Models\Academics\SubjectGroup;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with('group')->paginate(20);
        return view('academics.subjects.index', compact('subjects'));
    }

    public function create()
    {
        $groups = SubjectGroup::all();
        return view('academics.subjects.create', compact('groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code',
            'name' => 'required|string|max:255',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
        ]);

        Subject::create($request->all());

        return redirect()->route('academics.subjects.index')
            ->with('success','Subject created successfully.');
    }

    public function edit(Subject $subject)
    {
        $groups = SubjectGroup::all();
        return view('academics.subjects.edit', compact('subject','groups'));
    }

    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code,'.$subject->id,
            'name' => 'required|string|max:255',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
        ]);

        $subject->update($request->all());

        return redirect()->route('academics.subjects.index')
            ->with('success','Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('academics.subjects.index')
            ->with('success','Subject deleted.');
    }
}
