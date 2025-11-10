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
        $classrooms = Classroom::with(['teachers', 'streams', 'nextClass', 'previousClasses'])->get();
        return view('academics.classrooms.index', compact('classrooms'));
    }

    public function create()
    {
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        $classrooms = Classroom::orderBy('name')->get();
        
        // Get classes that are already selected as next_class_id by another class
        $usedAsNextClass = Classroom::whereNotNull('next_class_id')
            ->pluck('next_class_id')
            ->unique()
            ->toArray();
        
        return view('academics.classrooms.create', compact('teachers', 'classrooms', 'usedAsNextClass'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name',
            'next_class_id' => 'nullable|exists:classrooms,id',
            'is_beginner' => 'nullable|boolean',
            'is_alumni' => 'nullable|boolean',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $classroom = Classroom::create([
            'name' => $request->name,
            'next_class_id' => $request->next_class_id,
            'is_beginner' => $request->boolean('is_beginner'),
            'is_alumni' => $request->boolean('is_alumni'),
        ]);

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
        $classrooms = Classroom::where('id', '!=', $id)->orderBy('name')->get();
        
        // Get classes that are already selected as next_class_id by another class (excluding current class's next_class_id)
        $usedAsNextClass = Classroom::whereNotNull('next_class_id')
            ->where('id', '!=', $id)
            ->where('next_class_id', '!=', $classroom->next_class_id)
            ->pluck('next_class_id')
            ->unique()
            ->toArray();

        return view('academics.classrooms.edit', compact('classroom', 'teachers', 'assignedTeachers', 'classrooms', 'usedAsNextClass'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classrooms,name,' . $id,
            'next_class_id' => 'nullable|exists:classrooms,id|different:id',
            'is_beginner' => 'nullable|boolean',
            'is_alumni' => 'nullable|boolean',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $classroom = Classroom::findOrFail($id);
        $classroom->update([
            'name' => $request->name,
            'next_class_id' => $request->next_class_id,
            'is_beginner' => $request->boolean('is_beginner'),
            'is_alumni' => $request->boolean('is_alumni'),
        ]);

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
