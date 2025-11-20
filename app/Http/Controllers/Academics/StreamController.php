<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Academics\Stream;
use App\Models\Academics\Classroom;

class StreamController extends Controller
{
    public function index()
    {
        $streams = Stream::with(['classroom', 'classrooms', 'teachers'])->orderBy('name')->get();
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
            'name' => 'required|string|max:255|unique:streams,name',
            'classroom_id' => 'required|exists:classrooms,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $stream = Stream::create([
            'name' => $request->name,
            'classroom_id' => $request->classroom_id,
        ]);

        // Attach additional classrooms via pivot if provided
        if ($request->has('classroom_ids')) {
            $stream->classrooms()->sync($request->classroom_ids);
        }

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream added successfully.');
    }

    public function edit($id)
    {
        $stream = Stream::findOrFail($id);
        $classrooms = Classroom::orderBy('name')->get();
        $assignedClassrooms = $stream->classrooms->pluck('id')->toArray();

        return view('academics.streams.edit', compact('stream', 'classrooms', 'assignedClassrooms'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:streams,name,' . $id,
            'classroom_id' => 'required|exists:classrooms,id',
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $stream = Stream::findOrFail($id);
        $stream->update([
            'name' => $request->name,
            'classroom_id' => $request->classroom_id,
        ]);
        
        // Sync additional classrooms via pivot
        $stream->classrooms()->sync($request->classroom_ids ?? []);

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
        ]);

        // Double-check: if stream_id is provided in request, ensure it matches the route parameter
        if ($request->has('stream_id') && (int)$request->stream_id !== (int)$id) {
            return redirect()->route('academics.assign-teachers')
                ->with('error', 'Stream ID mismatch. Assignment cancelled for security.');
        }

        // Get current teachers for this specific stream before assignment
        $currentTeachers = $stream->teachers->pluck('id')->toArray();
        $newTeachers = $request->teacher_ids ?? [];

        // Only sync teachers for THIS specific stream
        $stream->teachers()->sync($newTeachers);

        \Log::info('Stream teacher assignment', [
            'stream_id' => $stream->id,
            'stream_name' => $stream->name,
            'classroom_id' => $stream->classroom_id,
            'previous_teachers' => $currentTeachers,
            'new_teachers' => $newTeachers,
        ]);

        return redirect()->route('academics.assign-teachers')
            ->with('success', "Teachers assigned to '{$stream->name}' stream in '{$stream->classroom->name}' successfully.");
    }

    public function destroy($id)
    {
        $stream = Stream::findOrFail($id);
        $stream->classrooms()->detach();
        $stream->delete();

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream deleted successfully.');
    }
}
