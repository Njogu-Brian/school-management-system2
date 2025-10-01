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
        $streams = Stream::with('classrooms')->get();
        return view('academics.streams.index', compact('streams'));
    }

    public function create()
    {
        $classrooms = Classroom::all();
        return view('academics.streams.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:streams,name',
            'classroom_ids' => 'required|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $stream = Stream::create(['name' => $request->name]);
        $stream->classroomss()->attach($request->classrooms_ids);

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream added successfully.');
    }

    public function edit($id)
    {
        $stream = Stream::findOrFail($id);
        $classrooms = Classroom::all();
        $assignedClassrooms = $stream->classroomss->pluck('id')->toArray();

        return view('academics.streams.edit', compact('stream', 'classrooms', 'assignedClassrooms'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:streams,name,' . $id,
            'classroom_ids' => 'required|array',
            'classroom_ids.*' => 'exists:classrooms,id',
        ]);

        $stream = Stream::findOrFail($id);
        $stream->update(['name' => $request->name]);
        $stream->classroomss()->sync($request->classrooms_ids);

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream updated successfully.');
    }

    public function destroy($id)
    {
        $stream = Stream::findOrFail($id);
        $stream->classroomss()->detach();
        $stream->delete();

        return redirect()->route('academics.streams.index')
            ->with('success', 'Stream deleted successfully.');
    }
}
