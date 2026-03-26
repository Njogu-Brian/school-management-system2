<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignTeachersController extends Controller
{
    public function index()
    {
        // Get classrooms with their streams (primary + additional via pivot)
        $classrooms = Classroom::with(['teachers', 'streams.teachers', 'primaryStreams.teachers'])->orderBy('name')->get();
        
        // For each classroom, merge primary and additional streams
        foreach ($classrooms as $classroom) {
            $allStreams = $classroom->primaryStreams->merge($classroom->streams)->unique('id');
            $classroom->setRelation('streams', $allStreams);
        }
        
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))->get();
        
        return view('academics.assign_teachers', compact('classrooms', 'teachers'));
    }

    /**
     * Assign teachers directly to a classroom (for classes without streams)
     */
    public function assignToClassroom(Request $request, $id)
    {
        $classroom = Classroom::findOrFail($id);
        
        $request->validate([
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
        ]);

        $classroom->teachers()->sync($request->teacher_ids ?? []);

        return redirect()->route('academics.assign-teachers')
            ->with('success', 'Teachers assigned to ' . $classroom->name . ' successfully.');
    }

    /**
     * Remove all teachers assigned to classrooms (classroom_teacher) and streams (stream_teacher).
     */
    public function clearAllAssignments(Request $request)
    {
        $request->validate([
            'confirm_clear' => 'required|in:CLEARALL',
        ]);

        Schema::disableForeignKeyConstraints();

        try {
            if (Schema::hasTable('classroom_teacher')) {
                DB::table('classroom_teacher')->delete();
            }
            if (Schema::hasTable('stream_teacher')) {
                DB::table('stream_teacher')->delete();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return redirect()
            ->route('academics.assign-teachers')
            ->with('success', 'All classroom and stream teacher assignments have been cleared. You can assign teachers again.');
    }
}
