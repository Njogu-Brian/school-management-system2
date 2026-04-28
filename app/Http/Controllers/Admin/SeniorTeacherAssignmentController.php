<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CampusSeniorTeacher;
use App\Models\SeniorTeacherClassroomAssignment;
use App\Models\Academics\Classroom;
use Illuminate\Support\Facades\DB;

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
        $allClassrooms = Classroom::orderBy('name')->get(['id', 'name', 'campus', 'level_type']);
        $assignedClassroomIds = SeniorTeacherClassroomAssignment::where('senior_teacher_id', $seniorTeacher->id)
            ->pluck('classroom_id')
            ->toArray();

        return view('admin.senior_teacher_assignments.edit', compact('seniorTeacher', 'campusAssignment', 'allClassrooms', 'assignedClassroomIds'));
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

        // Remove this senior teacher's previous campus assignment (so they only have one).
        CampusSeniorTeacher::where('senior_teacher_id', $seniorTeacher->id)->delete();

        CampusSeniorTeacher::updateOrCreate(
            ['campus' => $campus, 'senior_teacher_id' => $seniorTeacher->id],
            []
        );

        return redirect()->route('admin.senior_teacher_assignments.edit', $id)
            ->with('success', 'Campus assignment updated. You can optionally restrict supervision to specific classrooms below.');
    }

    /**
     * Assign exact classrooms supervised by this senior teacher.
     * When classrooms are selected, they override campus-wide supervision scope.
     */
    public function updateClassrooms(Request $request, $id)
    {
        $seniorTeacher = User::whereHas('roles', function ($q) {
            $q->where('name', 'Senior Teacher');
        })->findOrFail($id);

        $v = $request->validate([
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'integer|exists:classrooms,id',
        ]);

        $classroomIds = array_values(array_unique(array_map('intval', $v['classroom_ids'] ?? [])));

        DB::transaction(function () use ($seniorTeacher, $classroomIds) {
            // Remove existing assignments for this senior teacher
            SeniorTeacherClassroomAssignment::where('senior_teacher_id', $seniorTeacher->id)->delete();

            if ($classroomIds === []) {
                return;
            }

            // Ensure a classroom belongs to only one senior teacher by clearing it from others.
            SeniorTeacherClassroomAssignment::whereIn('classroom_id', $classroomIds)
                ->where('senior_teacher_id', '!=', $seniorTeacher->id)
                ->delete();

            $now = now();
            $rows = array_map(fn ($cid) => [
                'senior_teacher_id' => $seniorTeacher->id,
                'classroom_id' => $cid,
                'created_at' => $now,
                'updated_at' => $now,
            ], $classroomIds);

            SeniorTeacherClassroomAssignment::insert($rows);
        });

        return redirect()
            ->route('admin.senior_teacher_assignments.edit', $id)
            ->with('success', 'Supervised classrooms updated.');
    }
}
