<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Staff;
use Illuminate\Http\Request;

class AssignTeachersController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::with('classTeacher')->orderBy('name')->get();

        $teacherRoleNames = ['Teacher', 'teacher', 'Senior Teacher', 'senior teacher', 'Supervisor', 'supervisor'];
        $staffTeachers = Staff::with('user')
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', $teacherRoleNames))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('academics.assign_teachers', [
            'classrooms' => $classrooms,
            'staffTeachers' => $staffTeachers,
        ]);
    }

    /**
     * Assign/clear class teacher for a classroom (classrooms.class_teacher_id → staff.id).
     */
    public function assignClassTeacher(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        $request->validate([
            'staff_id' => 'nullable|integer|exists:staff,id',
        ]);

        $classroom->class_teacher_id = $request->filled('staff_id') ? (int) $request->staff_id : null;
        $classroom->save();

        return redirect()->route('academics.assign-teachers')
            ->with('success', 'Class teacher updated for ' . $classroom->name . ' successfully.');
    }

    /**
     * Clear class teacher assignments for all classrooms.
     */
    public function clearAllAssignments(Request $request)
    {
        $request->validate([
            'confirm_clear' => 'required|in:CLEARALL',
        ]);

        Classroom::query()->update(['class_teacher_id' => null]);

        return redirect()
            ->route('academics.assign-teachers')
            ->with('success', 'All class teacher assignments have been cleared. You can assign again.');
    }
}
