<?php

namespace App\Http\Controllers;

use App\Models\Votehead;
use Illuminate\Http\Request;

class VoteheadController extends Controller
{
    public function index()
    {
        $voteheads = Votehead::all();
        return view('finance.voteheads.index', compact('voteheads'));
    }

    public function create()
    {
        return view('finance.voteheads.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:voteheads,name',
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
        ]);

        Votehead::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_mandatory' => $request->has('is_mandatory'),
        ]);

        return redirect()->route('voteheads.index')->with('success', 'Votehead created successfully.');
    }

    public function edit(Votehead $votehead)
    {
        return view('finance.voteheads.edit', compact('votehead'));
    }

    public function update(Request $request, Votehead $votehead)
    {
        $request->validate([
            'name' => 'required|unique:voteheads,name,' . $votehead->id,
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
        ]);

        $votehead->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_mandatory' => $request->has('is_mandatory'),
        ]);

        return redirect()->route('voteheads.index')->with('success', 'Votehead updated successfully.');
    }

    public function destroy(Votehead $votehead)
    {
        $votehead->delete();
        return redirect()->route('voteheads.index')->with('success', 'Votehead deleted successfully.');
    }
}
