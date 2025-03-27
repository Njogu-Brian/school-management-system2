<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User; // Assuming User model handles teacher accounts
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::with('teachers')->get();
        return view('classrooms.index', compact('classrooms'));
        $classrooms = Classroom::with('streams')->get();
        return view('classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        // Fetch teachers using roles
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->get();

        return view('classrooms.create', compact('teachers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        // Create the classroom
        $classroom = Classroom::create(['name' => $request->name]);

        // Attach teachers if provided
        if ($request->has('teacher_ids')) {
            $classroom->teachers()->sync($request->teacher_ids);
        }

        return redirect()->route('classrooms.index')->with('success', 'Classroom added successfully.');
    }

    public function edit($id)
    {
        $classroom = Classroom::findOrFail($id);
        
        // Fetch teachers and current assigned teachers
        $teachers = User::whereHas('roles', function ($query) {
            $query->where('name', 'teacher');
        })->get();

        $assignedTeachers = $classroom->teachers->pluck('id')->toArray();

        return view('classrooms.edit', compact('classroom', 'teachers', 'assignedTeachers'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name,'.$id,
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $classroom = Classroom::findOrFail($id);
        $classroom->update(['name' => $request->name]);

        // Update teacher assignments
        if ($request->has('teacher_ids')) {
            $classroom->teachers()->sync($request->teacher_ids);
        } else {
            $classroom->teachers()->detach();
        }

        return redirect()->route('classrooms.index')->with('success', 'Classroom updated successfully.');
    }

    public function destroy($id)
    {
        $classroom = Classroom::findOrFail($id);

        // Detach teachers before deleting the classroom
        $classroom->teachers()->detach();
        $classroom->delete();

        return redirect()->route('classrooms.index')->with('success', 'Classroom deleted successfully.');
    }
}
