<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\SubjectGroup;
use Illuminate\Http\Request;

class SubjectGroupController extends Controller
{
    public function index()
    {
        $groups = SubjectGroup::orderBy('display_order')->paginate(20);
        return view('academics.subject_groups.index', compact('groups'));
    }

    public function create()
    {
        return view('academics.subject_groups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:subject_groups,name',
            'code' => 'nullable|string|max:50|unique:subject_groups,code',
        ]);

        SubjectGroup::create($request->only('name','code','display_order','description'));

        return redirect()->route('academics.subject-groups.index')
            ->with('success','Subject group created successfully.');
    }

    public function edit(SubjectGroup $subject_group)
    {
        return view('academics.subject_groups.edit', compact('subject_group'));
    }

    public function update(Request $request, SubjectGroup $subject_group)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:subject_groups,name,'.$subject_group->id,
            'code' => 'nullable|string|max:50|unique:subject_groups,code,'.$subject_group->id,
        ]);

        $subject_group->update($request->only('name','code','display_order','description'));

        return redirect()->route('academics.subject-groups.index')
            ->with('success','Subject group updated successfully.');
    }

    public function destroy(SubjectGroup $subject_group)
    {
        $subject_group->delete();
        return redirect()->route('academics.subject-groups.index')
            ->with('success','Subject group deleted.');
    }
}
