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
            'is_mandatory' => 'boolean',
            'charge_type' => 'required|in:per_student,once,once_annually,per_family',
        ]);

        Votehead::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'charge_type' => $request->charge_type,
        ]);

        return redirect()->route('finance.voteheads.index')->with('success', 'Votehead created successfully.');
    }

    public function update(Request $request, Votehead $votehead)
    {
        $request->validate([
            'name' => 'required|unique:voteheads,name,' . $votehead->id,
            'description' => 'nullable|string',
            'is_mandatory' => 'boolean',
            'charge_type' => 'required|in:per_student,once,once_annually,per_family',
        ]);

        $votehead->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'charge_type' => $request->charge_type,
        ]);

        return redirect()->route('finance.voteheads.index')->with('success', 'Votehead updated successfully.');
    }

        
    public function edit(Votehead $votehead)
    {
        return view('finance.voteheads.edit', compact('votehead'));
    }

    public function destroy(Votehead $votehead)
    {
        $votehead->delete();
        return redirect()->route('finance.voteheads.index')->with('success', 'Votehead deleted successfully.');
    }
}
