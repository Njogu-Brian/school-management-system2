<?php

namespace App\Http\Controllers;

use App\Models\Stream;
use App\Models\Classroom;
use Illuminate\Http\Request;

class StreamController extends Controller
{
    public function index()
    {
        $streams = Stream::with('classroom')->get();
        return view('streams.index', compact('streams'));
    }

    public function create()
    {
        $classrooms = Classroom::all();
        return view('streams.create', compact('classrooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:classrooms,id',
        ]);

        Stream::create($request->all());

        return redirect()->route('streams.index')->with('success', 'Stream added successfully.');
    }

    public function edit($id)
    {
        $stream = Stream::findOrFail($id);
        $classrooms = Classroom::all();
        return view('streams.edit', compact('stream', 'classrooms'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:classrooms,id',
        ]);

        $stream = Stream::findOrFail($id);
        $stream->update($request->all());

        return redirect()->route('streams.index')->with('success', 'Stream updated successfully.');
    }

    public function destroy($id)
    {
        $stream = Stream::findOrFail($id);
        $stream->delete();

        return redirect()->route('streams.index')->with('success', 'Stream deleted successfully.');
    }
}
