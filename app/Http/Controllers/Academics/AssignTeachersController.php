<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Staff;
use App\Models\ClassTeacherAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignTeachersController extends Controller
{
    public function index()
    {
        $classrooms = Classroom::with(['primaryStreams', 'streams'])->orderBy('name')->get();

        $teacherRoleNames = ['Teacher', 'teacher', 'Senior Teacher', 'senior teacher', 'Supervisor', 'supervisor'];
        $staffTeachers = Staff::with('user')
            ->whereHas('user.roles', fn ($q) => $q->whereIn('name', $teacherRoleNames))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $assignments = DB::table('class_teacher_assignments')->get(['classroom_id', 'stream_id', 'staff_id']);
        $assignmentMap = [];
        foreach ($assignments as $a) {
            $key = (int) $a->classroom_id . ':' . ($a->stream_id === null ? 'null' : (int) $a->stream_id);
            $assignmentMap[$key] = (int) $a->staff_id;
        }

        return view('academics.assign_teachers', [
            'classrooms' => $classrooms,
            'staffTeachers' => $staffTeachers,
            'assignmentMap' => $assignmentMap,
        ]);
    }

    /**
     * Assign/clear class teacher for a classroom or classroom-stream.
     */
    public function assignClassTeacher(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);

        $request->validate([
            'staff_id' => 'nullable|integer|exists:staff,id',
            'stream_id' => 'nullable|integer|exists:streams,id',
        ]);

        $streamId = $request->filled('stream_id') ? (int) $request->stream_id : null;

        if (! $request->filled('staff_id')) {
            ClassTeacherAssignment::query()
                ->where('classroom_id', $classroom->id)
                ->when($streamId === null, fn ($q) => $q->whereNull('stream_id'), fn ($q) => $q->where('stream_id', $streamId))
                ->delete();
        } else {
            ClassTeacherAssignment::updateOrCreate(
                ['classroom_id' => $classroom->id, 'stream_id' => $streamId],
                ['staff_id' => (int) $request->staff_id]
            );
        }

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

        DB::table('class_teacher_assignments')->delete();

        return redirect()
            ->route('academics.assign-teachers')
            ->with('success', 'All class teacher assignments have been cleared. You can assign again.');
    }
}
