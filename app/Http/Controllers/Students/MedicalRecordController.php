<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentMedicalRecord;
use App\Http\Requests\StoreMedicalRecordRequest;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    /**
     * Display a listing of medical records for a student
     */
    public function index(Request $request, Student $student)
    {
        $records = $student->medicalRecords()
            ->with('createdBy')
            ->orderByDesc('record_date')
            ->paginate(20);

        return view('students.records.medical.index', compact('student', 'records'));
    }

    /**
     * Show the form for creating a new medical record
     */
    public function create(Student $student)
    {
        return view('students.records.medical.create', compact('student'));
    }

    /**
     * Store a newly created medical record
     */
    public function store(StoreMedicalRecordRequest $request, Student $student)
    {
        $data = $request->validated();
        $data['student_id'] = $student->id;
        $data['created_by'] = auth()->id();

        StudentMedicalRecord::create($data);

        return redirect()->route('students.medical-records.index', $student)
            ->with('success', 'Medical record created successfully.');
    }

    /**
     * Display the specified medical record
     */
    public function show(Student $student, StudentMedicalRecord $medicalRecord)
    {
        $medicalRecord->load('createdBy');
        return view('students.records.medical.show', compact('student', 'medicalRecord'));
    }

    /**
     * Show the form for editing the specified medical record
     */
    public function edit(Student $student, StudentMedicalRecord $medicalRecord)
    {
        return view('students.records.medical.edit', compact('student', 'medicalRecord'));
    }

    /**
     * Update the specified medical record
     */
    public function update(StoreMedicalRecordRequest $request, Student $student, StudentMedicalRecord $medicalRecord)
    {
        $medicalRecord->update($request->validated());

        return redirect()->route('students.medical-records.index', $student)
            ->with('success', 'Medical record updated successfully.');
    }

    /**
     * Remove the specified medical record
     */
    public function destroy(Student $student, StudentMedicalRecord $medicalRecord)
    {
        $medicalRecord->delete();

        return redirect()->route('students.medical-records.index', $student)
            ->with('success', 'Medical record deleted successfully.');
    }
}
