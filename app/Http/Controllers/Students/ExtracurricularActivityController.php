<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentExtracurricularActivity;
use App\Http\Requests\StoreExtracurricularActivityRequest;
use Illuminate\Http\Request;

class ExtracurricularActivityController extends Controller
{
    /**
     * Display a listing of extracurricular activities for a student
     */
    public function index(Request $request, Student $student)
    {
        $activities = $student->extracurricularActivities()
            ->with('supervisor')
            ->orderByDesc('start_date')
            ->paginate(20);

        return view('students.records.activities.index', compact('student', 'activities'));
    }

    /**
     * Show the form for creating a new extracurricular activity
     */
    public function create(Student $student)
    {
        return view('students.records.activities.create', compact('student'));
    }

    /**
     * Store a newly created extracurricular activity
     */
    public function store(StoreExtracurricularActivityRequest $request, Student $student)
    {
        $data = $request->validated();
        $data['student_id'] = $student->id;

        StudentExtracurricularActivity::create($data);

        return redirect()->route('students.activities.index', $student)
            ->with('success', 'Extracurricular activity created successfully.');
    }

    /**
     * Display the specified extracurricular activity
     */
    public function show(Student $student, StudentExtracurricularActivity $activity)
    {
        $activity->load('supervisor');
        return view('students.records.activities.show', compact('student', 'activity'));
    }

    /**
     * Show the form for editing the specified extracurricular activity
     */
    public function edit(Student $student, StudentExtracurricularActivity $activity)
    {
        return view('students.records.activities.edit', compact('student', 'activity'));
    }

    /**
     * Update the specified extracurricular activity
     */
    public function update(StoreExtracurricularActivityRequest $request, Student $student, StudentExtracurricularActivity $activity)
    {
        $activity->update($request->validated());

        return redirect()->route('students.activities.index', $student)
            ->with('success', 'Extracurricular activity updated successfully.');
    }

    /**
     * Remove the specified extracurricular activity
     */
    public function destroy(Student $student, StudentExtracurricularActivity $activity)
    {
        $activity->delete();

        return redirect()->route('students.activities.index', $student)
            ->with('success', 'Extracurricular activity deleted successfully.');
    }
}
