<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAcademicHistory;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Http\Requests\StoreAcademicHistoryRequest;
use Illuminate\Http\Request;

class AcademicHistoryController extends Controller
{
    /**
     * Display a listing of academic history for a student
     */
    public function index(Request $request, Student $student)
    {
        $history = $student->academicHistory()
            ->with(['classroom', 'stream', 'promotedBy'])
            ->orderByDesc('enrollment_date')
            ->paginate(20);

        return view('students.records.academic.index', compact('student', 'history'));
    }

    /**
     * Show the form for creating a new academic history entry
     */
    public function create(Student $student)
    {
        $classrooms = Classroom::orderBy('name')->get();
        $streams = Stream::orderBy('name')->get();
        
        return view('students.records.academic.create', compact('student', 'classrooms', 'streams'));
    }

    /**
     * Store a newly created academic history entry
     */
    public function store(StoreAcademicHistoryRequest $request, Student $student)
    {
        $data = $request->validated();
        $data['student_id'] = $student->id;
        $data['promoted_by'] = auth()->id();

        // If this is marked as current, unmark all other current entries
        if ($request->boolean('is_current')) {
            StudentAcademicHistory::where('student_id', $student->id)
                ->update(['is_current' => false]);
        }

        StudentAcademicHistory::create($data);

        return redirect()->route('students.academic-history.index', $student)
            ->with('success', 'Academic history entry created successfully.');
    }

    /**
     * Display the specified academic history entry
     */
    public function show(Student $student, StudentAcademicHistory $academicHistory)
    {
        $academicHistory->load(['classroom', 'stream', 'promotedBy']);
        return view('students.records.academic.show', compact('student', 'academicHistory'));
    }

    /**
     * Show the form for editing the specified academic history entry
     */
    public function edit(Student $student, StudentAcademicHistory $academicHistory)
    {
        $classrooms = Classroom::orderBy('name')->get();
        $streams = Stream::orderBy('name')->get();
        
        return view('students.records.academic.edit', compact('student', 'academicHistory', 'classrooms', 'streams'));
    }

    /**
     * Update the specified academic history entry
     */
    public function update(StoreAcademicHistoryRequest $request, Student $student, StudentAcademicHistory $academicHistory)
    {
        $data = $request->validated();

        // If this is marked as current, unmark all other current entries
        if ($request->boolean('is_current')) {
            StudentAcademicHistory::where('student_id', $student->id)
                ->where('id', '!=', $academicHistory->id)
                ->update(['is_current' => false]);
        }

        $academicHistory->update($data);

        return redirect()->route('students.academic-history.index', $student)
            ->with('success', 'Academic history entry updated successfully.');
    }

    /**
     * Remove the specified academic history entry
     */
    public function destroy(Student $student, StudentAcademicHistory $academicHistory)
    {
        $academicHistory->delete();

        return redirect()->route('students.academic-history.index', $student)
            ->with('success', 'Academic history entry deleted successfully.');
    }
}
