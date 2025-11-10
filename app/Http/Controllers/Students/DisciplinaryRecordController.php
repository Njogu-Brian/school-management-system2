<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentDisciplinaryRecord;
use App\Http\Requests\StoreDisciplinaryRecordRequest;
use Illuminate\Http\Request;

class DisciplinaryRecordController extends Controller
{
    /**
     * Display a listing of disciplinary records for a student
     */
    public function index(Request $request, Student $student)
    {
        $records = $student->disciplinaryRecords()
            ->with(['reportedBy', 'actionTakenBy'])
            ->orderByDesc('incident_date')
            ->paginate(20);

        return view('students.records.disciplinary.index', compact('student', 'records'));
    }

    /**
     * Show the form for creating a new disciplinary record
     */
    public function create(Student $student)
    {
        return view('students.records.disciplinary.create', compact('student'));
    }

    /**
     * Store a newly created disciplinary record
     */
    public function store(StoreDisciplinaryRecordRequest $request, Student $student)
    {
        $data = $request->validated();
        $data['student_id'] = $student->id;
        $data['reported_by'] = auth()->id();

        StudentDisciplinaryRecord::create($data);

        return redirect()->route('students.disciplinary-records.index', $student)
            ->with('success', 'Disciplinary record created successfully.');
    }

    /**
     * Display the specified disciplinary record
     */
    public function show(Student $student, StudentDisciplinaryRecord $disciplinaryRecord)
    {
        $disciplinaryRecord->load(['reportedBy', 'actionTakenBy']);
        return view('students.records.disciplinary.show', compact('student', 'disciplinaryRecord'));
    }

    /**
     * Show the form for editing the specified disciplinary record
     */
    public function edit(Student $student, StudentDisciplinaryRecord $disciplinaryRecord)
    {
        return view('students.records.disciplinary.edit', compact('student', 'disciplinaryRecord'));
    }

    /**
     * Update the specified disciplinary record
     */
    public function update(StoreDisciplinaryRecordRequest $request, Student $student, StudentDisciplinaryRecord $disciplinaryRecord)
    {
        $data = $request->validated();
        if ($request->filled('action_taken_by')) {
            $data['action_taken_by'] = $request->action_taken_by;
        }

        $disciplinaryRecord->update($data);

        return redirect()->route('students.disciplinary-records.index', $student)
            ->with('success', 'Disciplinary record updated successfully.');
    }

    /**
     * Remove the specified disciplinary record
     */
    public function destroy(Student $student, StudentDisciplinaryRecord $disciplinaryRecord)
    {
        $disciplinaryRecord->delete();

        return redirect()->route('students.disciplinary-records.index', $student)
            ->with('success', 'Disciplinary record deleted successfully.');
    }
}
