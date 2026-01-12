<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Staff;
use App\Models\Academics\Classroom;
use Illuminate\Support\Facades\DB;

class SeniorTeacherAssignmentController extends Controller
{
    /**
     * Show the assignment form for a senior teacher
     */
    public function index()
    {
        // Get all users with Senior Teacher role
        $seniorTeachers = User::whereHas('roles', function($q) {
            $q->where('name', 'Senior Teacher');
        })->with(['staff', 'supervisedClassrooms', 'supervisedStaff'])->get();
        
        return view('admin.senior_teacher_assignments.index', compact('seniorTeachers'));
    }

    /**
     * Show the assignment form for a specific senior teacher
     */
    public function edit($id)
    {
        $seniorTeacher = User::whereHas('roles', function($q) {
            $q->where('name', 'Senior Teacher');
        })->with(['staff', 'supervisedClassrooms', 'supervisedStaff'])->findOrFail($id);
        
        $allClassrooms = Classroom::with('students')->orderBy('name')->get();
        
        // Get all active staff excluding the senior teacher themselves
        $allStaff = Staff::where('status', 'Active')
            ->where('id', '!=', $seniorTeacher->staff->id ?? null)
            ->with(['user', 'position'])
            ->orderBy('first_name')
            ->get();
        
        return view('admin.senior_teacher_assignments.edit', compact(
            'seniorTeacher',
            'allClassrooms',
            'allStaff'
        ));
    }

    /**
     * Update classroom assignments for a senior teacher
     */
    public function updateClassrooms(Request $request, $id)
    {
        $request->validate([
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $seniorTeacher = User::whereHas('roles', function($q) {
            $q->where('name', 'Senior Teacher');
        })->findOrFail($id);

        // Sync the supervised classrooms
        $seniorTeacher->supervisedClassrooms()->sync($request->classroom_ids ?? []);

        return redirect()->route('admin.senior_teacher_assignments.edit', $id)
            ->with('success', 'Supervised classrooms updated successfully.');
    }

    /**
     * Update staff assignments for a senior teacher
     */
    public function updateStaff(Request $request, $id)
    {
        $request->validate([
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'exists:staff,id',
        ]);

        $seniorTeacher = User::whereHas('roles', function($q) {
            $q->where('name', 'Senior Teacher');
        })->findOrFail($id);

        // Prevent assigning themselves
        $staffIds = $request->staff_ids ?? [];
        if ($seniorTeacher->staff && in_array($seniorTeacher->staff->id, $staffIds)) {
            return redirect()->back()
                ->with('error', 'A senior teacher cannot supervise themselves.');
        }

        // Sync the supervised staff
        $seniorTeacher->supervisedStaff()->sync($staffIds);

        return redirect()->route('admin.senior_teacher_assignments.edit', $id)
            ->with('success', 'Supervised staff updated successfully.');
    }

    /**
     * Bulk assign classrooms to multiple senior teachers
     */
    public function bulkAssign(Request $request)
    {
        $request->validate([
            'senior_teacher_id' => 'required|exists:users,id',
            'classroom_ids' => 'required|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $seniorTeacher = User::findOrFail($request->senior_teacher_id);
        
        // Add to existing assignments (not replace)
        $existingClassrooms = $seniorTeacher->supervisedClassrooms()->pluck('classrooms.id')->toArray();
        $newClassrooms = array_unique(array_merge($existingClassrooms, $request->classroom_ids));
        
        $seniorTeacher->supervisedClassrooms()->sync($newClassrooms);

        return redirect()->back()
            ->with('success', 'Classrooms assigned successfully.');
    }

    /**
     * Remove a classroom assignment
     */
    public function removeClassroom($seniorTeacherId, $classroomId)
    {
        $seniorTeacher = User::findOrFail($seniorTeacherId);
        $seniorTeacher->supervisedClassrooms()->detach($classroomId);

        return redirect()->back()
            ->with('success', 'Classroom removed from supervision.');
    }

    /**
     * Remove a staff assignment
     */
    public function removeStaff($seniorTeacherId, $staffId)
    {
        $seniorTeacher = User::findOrFail($seniorTeacherId);
        $seniorTeacher->supervisedStaff()->detach($staffId);

        return redirect()->back()
            ->with('success', 'Staff removed from supervision.');
    }
}

