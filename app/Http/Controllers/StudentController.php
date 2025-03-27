<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\StudentCategory;
use App\Models\Classroom;
use App\Models\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Import this at the top

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

        $classes = Classroom::all();

        return view('students.index', compact('students', 'classes'));
    }

    public function create()
    {
        $parents = ParentInfo::all();
        $categories = StudentCategory::all();
        $classes = Classroom::all();
        $streams = Stream::all();
        $students = Student::with('parent')->get();

        // Make sure 'classes' is passed as 'classrooms' to match the view
        return view('students.create', compact('parents', 'categories', 'classes', 'streams', 'students'))->with('classrooms', $classes);
    }


    // ✅ Fetch Streams Based on Class
    public function getStreams(Request $request)
    {
        $classroomId = $request->classroom_id;

        // Get streams associated with the class
        $streams = Stream::whereHas('classrooms', function ($query) use ($classroomId) {
            $query->where('classrooms.id', $classroomId);
        })->get();

        return response()->json($streams);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'gender' => 'required|string',
                'dob' => 'nullable|date',
                'classroom_id' => 'nullable|exists:classrooms,id',
                'stream_id' => 'nullable|exists:streams,id',
                'category_id' => 'nullable|exists:student_categories,id',
                'nemis_number' => 'nullable|string',
                'knec_assessment_number' => 'nullable|string',
            ]);
    
            // ✅ Create Parent If Needed
            $parent = ParentInfo::create([
                'father_name' => $request->father_name,
                'father_phone' => $request->father_phone,
                'father_email' => $request->father_email,
                'father_id_number' => $request->father_id_number,
                'mother_name' => $request->mother_name,
                'mother_phone' => $request->mother_phone,
                'mother_email' => $request->mother_email,
                'mother_id_number' => $request->mother_id_number,
                'guardian_name' => $request->guardian_name,
                'guardian_phone' => $request->guardian_phone,
                'guardian_email' => $request->guardian_email,
                // 'guardian_id_number' => $request->guardian_id_number,
            ]);
    
            // Generate Admission Number
            $admission_number = Student::max('admission_number') + 1;
    
            // ✅ Create Student
            Student::create([
                'admission_number' => $admission_number,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender,
                'dob' => $request->dob,
                'parent_id' => $parent->id,
                'classroom_id' => $request->classroom_id,
                'stream_id' => $request->stream_id,
                'category_id' => $request->category_id,
                'nemis_number' => $request->nemis_number,
                'knec_assessment_number' => $request->knec_assessment_number,
            ]);
    
            return redirect()->route('students.index')->with('success', 'Student created successfully.');
        } catch (\Exception $e) {
            Log::error('Student Creation Failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return back()->with('error', 'Failed to create student. Please check logs for details.');
        }
    }
    
    public function update(Request $request, $id)
{
    $request->validate([
        'first_name' => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'last_name' => 'required|string|max:255',
        'gender' => 'required|string',
        'dob' => 'nullable|date',
        'parent_id' => 'nullable|exists:parent_info,id',
        'classroom_id' => 'nullable|exists:classrooms,id',
        'stream_id' => 'nullable|exists:streams,id',
        'category_id' => 'nullable|exists:student_categories,id',
        'nemis_number' => 'nullable|string',
        'knec_assessment_number' => 'nullable|string',
    ]);

    $student = Student::findOrFail($id);

    // ✅ Update Student Details
    $student->update($request->only([
        'first_name', 'middle_name', 'last_name', 'gender', 'dob',
        'classroom_id', 'stream_id', 'category_id',
        'nemis_number', 'knec_assessment_number'
    ]));

    // ✅ Check if Parent Exists or Create One
    $parent = ParentInfo::updateOrCreate(
        ['id' => $student->parent_id],
        [
            'father_name' => $request->father_name,
            'father_phone' => $request->father_phone,
            'father_email' => $request->father_email,
            'father_id_number' => $request->father_id_number,
            'mother_name' => $request->mother_name,
            'mother_phone' => $request->mother_phone,
            'mother_email' => $request->mother_email,
            'mother_id_number' => $request->mother_id_number,
            'guardian_name' => $request->guardian_name,
            'guardian_phone' => $request->guardian_phone,
            'guardian_email' => $request->guardian_email,
            // 'guardian_id_number' => $request->guardian_id_number,
        ]
    );

    // Ensure parent_id is set
    if (!$student->parent_id) {
        $student->update(['parent_id' => $parent->id]);
    }

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
    public function edit($id)
    {
        $student = Student::with('parent')->findOrFail($id);
        $parents = ParentInfo::all();
        $categories = StudentCategory::all();
        $classes = Classroom::all();

        // Fetch streams associated with the student's class
        $streams = Stream::whereHas('classrooms', function ($query) use ($student) {
            $query->where('classrooms.id', $student->classroom_id);
        })->get();

        return view('students.edit', compact('student', 'parents', 'categories', 'classes', 'streams'));
    }

}
