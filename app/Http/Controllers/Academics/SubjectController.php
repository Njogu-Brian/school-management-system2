<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Subject;
use App\Models\Academics\SubjectGroup;
use App\Models\Academics\Classroom;
use App\Models\User;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with(['group','classrooms','teachers'])->paginate(20);
        return view('academics.subjects.index', compact('subjects'));
    }

    public function create()
    {
        $groups = SubjectGroup::all();
        $classrooms = Classroom::all();
        $teachers = User::whereHas('roles', fn($q) => $q->where('name','teacher'))->get();

        return view('academics.subjects.create', compact('groups','classrooms','teachers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code',
            'name' => 'required|string|max:255',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $subject = Subject::create($request->only('code','name','subject_group_id','learning_area'));

        $subject->classroomss()->sync($request->classrooms_ids ?? []);
        $subject->teachers()->sync($request->teacher_ids ?? []);

        return redirect()->route('academics.subjects.index')
            ->with('success','Subject created successfully.');
    }

    public function edit(Subject $subject)
    {
        $groups = SubjectGroup::all();
        $classrooms = Classroom::all();
        $teachers = User::whereHas('roles', fn($q) => $q->where('name','teacher'))->get();

        $assignedClassrooms = $subject->classroomss->pluck('id')->toArray();
        $assignedTeachers = $subject->teachers->pluck('id')->toArray();

        return view('academics.subjects.edit', compact(
            'subject','groups','classrooms','teachers','assignedClassrooms','assignedTeachers'
        ));
    }

    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code,'.$subject->id,
            'name' => 'required|string|max:255',
            'subject_group_id' => 'nullable|exists:subject_groups,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $subject->update($request->only('code','name','subject_group_id','learning_area'));

        $subject->classroomss()->sync($request->classrooms_ids ?? []);
        $subject->teachers()->sync($request->teacher_ids ?? []);

        return redirect()->route('academics.subjects.index')
            ->with('success','Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $subject->classroomss()->detach();
        $subject->teachers()->detach();
        $subject->delete();

        return redirect()->route('academics.subjects.index')
            ->with('success','Subject deleted.');
    }
}
