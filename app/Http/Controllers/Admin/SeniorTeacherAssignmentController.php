<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CampusSeniorTeacher;

class SeniorTeacherAssignmentController extends Controller
{
    /**
     * List senior teachers and their campus assignment only.
     */
    public function index()
    {
        $seniorTeachers = User::whereHas('roles', function ($q) {
            $q->where('name', 'Senior Teacher');
        })->with(['staff', 'campusAssignment'])->get();

        $campusAssignments = CampusSeniorTeacher::all()->keyBy('senior_teacher_id');

        return view('admin.senior_teacher_assignments.index', compact('seniorTeachers', 'campusAssignments'));
    }

    /**
     * Edit page: assign senior teacher to a campus only.
     */
    public function edit($id)
    {
        $seniorTeacher = User::whereHas('roles', function ($q) {
            $q->where('name', 'Senior Teacher');
        })->with(['staff', 'campusAssignment'])->findOrFail($id);

        $campusAssignment = CampusSeniorTeacher::where('senior_teacher_id', $seniorTeacher->id)->first();

        return view('admin.senior_teacher_assignments.edit', compact('seniorTeacher', 'campusAssignment'));
    }

    /**
     * Assign a senior teacher to a campus. Scope = campus only (no separate classroom/staff assignment).
     */
    public function updateCampus(Request $request, $id)
    {
        $request->validate([
            'campus' => 'required|in:lower,upper',
        ]);

        $seniorTeacher = User::whereHas('roles', function ($q) {
            $q->where('name', 'Senior Teacher');
        })->findOrFail($id);

        $campus = $request->campus;

        // Remove this senior teacher's previous campus assignment (so they only have one)
        CampusSeniorTeacher::where('senior_teacher_id', $seniorTeacher->id)->delete();

        // Remove any other teacher from the target campus, then assign this teacher
        CampusSeniorTeacher::where('campus', $campus)
            ->where('senior_teacher_id', '!=', $seniorTeacher->id)
            ->delete();

        CampusSeniorTeacher::updateOrCreate(
            ['campus' => $campus],
            ['senior_teacher_id' => $seniorTeacher->id]
        );

        return redirect()->route('admin.senior_teacher_assignments.edit', $id)
            ->with('success', 'Campus assignment updated. Supervision scope is now all classrooms and staff on this campus.');
    }
}
