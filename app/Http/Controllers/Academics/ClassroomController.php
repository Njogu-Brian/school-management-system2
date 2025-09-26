<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\User;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::with(['teachers', 'streams'])->get();
        return view('academics.classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        return view('academics.classrooms.create', compact('teachers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $classroom = Classroom::create(['name' => $request->name]);

        if ($request->has('teacher_ids')) {
            $classroom->teachers()->sync($request->teacher_ids);
        }

        return redirect()->route('academics.classrooms.index')
            ->with('success', 'Classroom added successfully.');
    }

    public function edit($id)
    {
        $classroom = Classroom::findOrFail($id);
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        $assignedTeachers = $classroom->teachers->pluck('id')->toArray();

        return view('academics.classrooms.edit', compact('classroom', 'teachers', 'assignedTeachers'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name,' . $id,
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $classroom = Classroom::findOrFail($id);
        $classroom->update(['name' => $request->name]);

        $classroom->teachers()->sync($request->teacher_ids ?? []);

        return redirect()->route('academics.classrooms.index')
            ->with('success', 'Classroom updated successfully.');
    }

    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);
        $classroom->teachers()->detach();
        $classroom->delete();

        return redirect()->route('academics.classrooms.index')
            ->with('success', 'Classroom deleted successfully.');
    }
}
