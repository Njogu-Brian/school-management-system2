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
        // Get classrooms ordered by name
        $classrooms = Classroom::with('teachers')->orderBy('name')->get();
        
        // Load streams - check both direct classroom_id and pivot table relationships
        $allStreams = Stream::with('teachers', 'classroom', 'classrooms')->get();
        
        // Group streams by classroom_id (direct relationship)
        $streamsByClassroom = $allStreams->groupBy('classroom_id');
        
        // Also check streams linked via pivot table
        foreach ($allStreams as $stream) {
            foreach ($stream->classrooms as $linkedClassroom) {
                if (!isset($streamsByClassroom[$linkedClassroom->id])) {
                    $streamsByClassroom[$linkedClassroom->id] = collect();
                }
                // Add stream if not already in collection for this classroom
                if (!$streamsByClassroom[$linkedClassroom->id]->contains('id', $stream->id)) {
                    $streamsByClassroom[$linkedClassroom->id]->push($stream);
                }
            }
        }
        
        // Attach streams to classrooms
        foreach ($classrooms as $classroom) {
            // Get streams from direct relationship
            $directStreams = $streamsByClassroom->get($classroom->id, collect());
            
            // Also get streams from pivot table
            $pivotStreams = $classroom->streams;
            
            // Merge both collections, removing duplicates
            $classroom->streams = $directStreams->merge($pivotStreams)->unique('id');
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
