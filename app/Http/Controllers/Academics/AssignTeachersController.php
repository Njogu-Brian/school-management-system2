<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\User;

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
    public function assignToClassroom(\Illuminate\Http\Request $request, $id)
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
}
