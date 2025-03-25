<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ParentInfo;

class StudentController extends Controller
{
    // Display all active students
    public function index()
    {
        $students = Student::where('archive', false)->get();
        return view('students.index', compact('students'));
    }

    // Show form to create student
    public function create()
    {
        $parents = ParentInfo::all();
        return view('students.create', compact('parents'));
    }

    // Store student details
    public function store(Request $request)
    {
        $request->validate([
            'admission_number' => 'required|unique:students',
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:parent_info,id',
        ]);

        Student::create([
            'admission_number' => $request->admission_number,
            'name' => $request->name,
            'class' => $request->class,
            'parent_id' => $request->parent_id,
        ]);

        return redirect()->route('students.index')->with('success', 'Student added successfully.');
    }

    // Show form to edit student
    public function edit($id)
    {
        $student = Student::findOrFail($id);
        $parents = ParentInfo::all();
        return view('students.edit', compact('student', 'parents'));
    }

    // Update student details
    public function update(Request $request, $id)
    {
        $request->validate([
            'admission_number' => 'required|unique:students,admission_number,' . $id,
            'name' => 'required|string|max:255',
            'class' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:parent_info,id',
        ]);

        $student = Student::findOrFail($id);
        $student->update([
            'admission_number' => $request->admission_number,
            'name' => $request->name,
            'class' => $request->class,
            'parent_id' => $request->parent_id,
        ]);

        return redirect()->route('students.index')->with('success', 'Student updated successfully.');
    }

    // Archive student instead of deleting
    public function archive($id)
    {
        $student = Student::findOrFail($id);
        $student->update(['archive' => true]);

        return redirect()->route('students.index')->with('success', 'Student archived successfully.');
    }

    // Restore archived student
    public function restore($id)
    {
        $student = Student::findOrFail($id);
        $student->update(['archive' => false]);

        return redirect()->route('students.index')->with('success', 'Student restored successfully.');
    }
}
