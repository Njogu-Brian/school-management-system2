<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Academics\Stream;
use App\Models\Academics\Classroom;

class StreamController extends Controller
{
    public function index()
    {
        $streams = Stream::with(['classroom', 'teachers'])->orderBy('classroom_id')->orderBy('name')->get();
        return view('academics.streams.index', compact('streams'));
    }

    public function create()
    {
        $classrooms = Classroom::orderBy('name')->get();
        return view('academics.streams.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per classroom: same name can exist in different classrooms
                Rule::unique('streams')->where(function ($query) use ($request) {
                    return $query->where('classroom_id', $request->classroom_id);
                }),
            ],
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $stream = Stream::create([
            'name' => $request->name,
            'classroom_id' => $request->classroom_id,
        ]);

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream added successfully.');
    }

    public function edit($id)
    {
        $stream = Stream::findOrFail($id);
        $classrooms = Classroom::orderBy('name')->get();

        return view('academics.streams.edit', compact('stream', 'classrooms'));
    }

    public function update(Request $request, $id)
    {
        $stream = Stream::findOrFail($id);
        
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per classroom: same name can exist in different classrooms
                Rule::unique('streams')->where(function ($query) use ($request) {
                    return $query->where('classroom_id', $request->classroom_id);
                })->ignore($id),
            ],
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $stream->update([
            'name' => $request->name,
            'classroom_id' => $request->classroom_id,
        ]);

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream updated successfully.');
    }

    public function assignTeachers(Request $request, $id)
    {
        $stream = Stream::findOrFail($id);
        
        $request->validate([
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:users,id',
            'stream_id' => 'nullable|exists:streams,id',
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        // Double-check: if stream_id is provided in request, ensure it matches the route parameter
        if ($request->has('stream_id') && (int)$request->stream_id !== (int)$id) {
            return redirect()->route('academics.assign-teachers')
                ->with('error', 'Stream ID mismatch. Assignment cancelled for security.');
        }

        $classroomId = $request->classroom_id;
        
        // Verify the classroom_id matches the stream's classroom (each stream belongs to one classroom)
        if ($stream->classroom_id != $classroomId) {
            return redirect()->route('academics.assign-teachers')
                ->with('error', 'Invalid classroom for this stream. Assignment cancelled.');
        }

        // Get current teachers for this specific stream-classroom combination
        $currentTeachers = \DB::table('stream_teacher')
            ->where('stream_id', $stream->id)
            ->where('classroom_id', $classroomId)
            ->pluck('teacher_id')
            ->toArray();
        
        $newTeachers = $request->teacher_ids ?? [];

        // Delete existing assignments for this stream-classroom combination
        \DB::table('stream_teacher')
            ->where('stream_id', $stream->id)
            ->where('classroom_id', $classroomId)
            ->delete();

        // Insert new assignments with classroom_id
        $insertData = [];
        foreach ($newTeachers as $teacherId) {
            $insertData[] = [
                'stream_id' => $stream->id,
                'teacher_id' => $teacherId,
                'classroom_id' => $classroomId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($insertData)) {
            \DB::table('stream_teacher')->insert($insertData);
        }

        \Log::info('Stream teacher assignment', [
            'stream_id' => $stream->id,
            'stream_name' => $stream->name,
            'classroom_id' => $classroomId,
            'previous_teachers' => $currentTeachers,
            'new_teachers' => $newTeachers,
        ]);

        $classroom = Classroom::find($classroomId);
        return redirect()->route('academics.assign-teachers')
            ->with('success', "Teachers assigned to '{$stream->name}' stream in '{$classroom->name}' successfully.");
    }

    public function destroy($id)
    {
        $stream = Stream::findOrFail($id);
        
        // Delete teacher assignments first
        DB::table('stream_teacher')
            ->where('stream_id', $stream->id)
            ->delete();
        
        $stream->delete();

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream deleted successfully.');
    }
}
