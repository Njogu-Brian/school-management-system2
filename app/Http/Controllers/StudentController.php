<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\StudentCategory;
use App\Models\Classroom;
use App\Models\Stream;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
{
    $students = Student::with(['parent', 'classroom', 'stream', 'category'])
        ->when($request->name, function ($query, $name) {
            $query->where('name', 'like', "%$name%");
        })
        ->when($request->admission_number, function ($query, $admission_number) {
            $query->where('admission_number', $admission_number);
        })
        ->when($request->classroom_id, function ($query, $classroom_id) {
            $query->where('classroom_id', $classroom_id);
        })
        ->where('archive', false)
        ->get();

    $classes = Classroom::all(); // Fetch all classes to be passed to the view

    return view('students.index', compact('students', 'classes'));
}


public function create()
{
    $parents = ParentInfo::all();
    $categories = StudentCategory::all();
    $classes = Classroom::all();
    $streams = Stream::all();
    $students = Student::with('parent')->get(); // Fetch all students for sibling management

    return view('students.create', compact('parents', 'categories', 'classes', 'streams', 'students'));
}


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required',
            'dob' => 'nullable|date',
            'parent_id' => 'nullable|exists:parent_info,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'nullable|exists:student_categories,id',
        ]);

        $admission_number = Student::max('admission_number') + 1;

        Student::create([
            'admission_number' => $admission_number,
            'name' => $request->name,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'parent_id' => $request->parent_id,
            'classroom_id' => $request->classroom_id,
            'stream_id' => $request->stream_id,
            'category_id' => $request->category_id,
        ]);

        return redirect()->route('students.index')->with('success', 'Student created successfully.');
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        $parents = ParentInfo::all();
        $categories = StudentCategory::all();
        $classes = Classroom::all();
        $streams = Stream::all();

        return view('students.edit', compact('student', 'parents', 'categories', 'classes', 'streams'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required',
            'dob' => 'nullable|date',
            'parent_id' => 'nullable|exists:parent_info,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'nullable|exists:student_categories,id',
        ]);

        $student = Student::findOrFail($id);

        $student->update($request->only([
            'name', 'gender', 'dob', 'parent_id', 'classroom_id', 'stream_id', 'category_id'
        ]));

        return redirect()->route('students.index')->with('success', 'Student updated successfully.');
    }

    public function archive($id)
    {
        $student = Student::findOrFail($id);
        $student->update(['archive' => true]);

        return redirect()->route('students.index')->with('success', 'Student archived.');
    }

    public function restore($id)
    {
        $student = Student::findOrFail($id);
        $student->update(['archive' => false]);

        return redirect()->route('students.index')->with('success', 'Student restored.');
    }
}
