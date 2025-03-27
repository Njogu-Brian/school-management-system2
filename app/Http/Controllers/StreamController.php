<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Models\Classroom;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    // Display all streams with their respective classrooms
    public function index()
    {
        $streams = Stream::with('classrooms')->get();
        return view('streams.index', compact('streams'));
    }

    // Show the form for creating a new stream
    public function create()
    {
        $classrooms = Classroom::all();
        return view('streams.create', compact('classrooms'));
    }

    // Store a newly created stream in the database
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'classroom_ids' => 'required|array',
        'classroom_ids.*' => 'exists:classrooms,id',
    ]);

    $stream = Stream::create([
        'name' => $request->name,
    ]);

    // Attach selected classrooms
    $stream->classrooms()->attach($request->classroom_ids);

    return redirect()->route('streams.index')->with('success', 'Stream added successfully.');
}

    // Show the form for editing the specified stream
    public function edit($id)
    {
        $stream = Stream::findOrFail($id);
        $classrooms = Classroom::all();
        return view('streams.edit', compact('stream', 'classrooms'));
    }

    // Update the specified stream in the database
    public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'classroom_ids' => 'required|array',
        'classroom_ids.*' => 'exists:classrooms,id',
    ]);

    $stream = Stream::findOrFail($id);

    $stream->update([
        'name' => $request->name,
    ]);

    // Sync classrooms to update the relationship
    $stream->classrooms()->sync($request->classroom_ids);

    return redirect()->route('streams.index')->with('success', 'Stream updated successfully.');
}


    // Delete the specified stream from the database
    public function destroy($id)
    {
        $stream = Stream::find($id);

        if (!$stream) {
            return redirect()->route('streams.index')->with('error', 'Stream not found.');
        }

        $stream->delete();
        return redirect()->route('streams.index')->with('success', 'Stream deleted successfully.');
    }
}
