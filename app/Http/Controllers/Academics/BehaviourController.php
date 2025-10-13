<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Behaviour;
use Illuminate\Http\Request;

class BehaviourController extends Controller
{
    public function index()
    {
        $behaviours = Behaviour::orderBy('name')->paginate(20);
        return view('academics.behaviours.index', compact('behaviours'));
    }

    public function create()
    {
        return view('academics.behaviours.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:positive,negative',
            'description' => 'nullable|string',
        ]);

        Behaviour::create($data);

        return redirect()->route('academics.behaviours.index')
            ->with('success', 'Behaviour category added successfully.');
    }

    public function edit(Behaviour $behaviour)
    {
        return view('academics.behaviours.edit', compact('behaviour'));
    }

    public function update(Request $request, Behaviour $behaviour)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:positive,negative',
            'description' => 'nullable|string',
        ]);

        $behaviour->update($data);

        return redirect()->route('academics.behaviours.index')
            ->with('success', 'Behaviour category updated successfully.');
    }

    public function destroy(Behaviour $behaviour)
    {
        $behaviour->delete();
        return back()->with('success', 'Behaviour deleted.');
    }
}
