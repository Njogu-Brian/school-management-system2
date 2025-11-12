<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;
use Illuminate\Http\Request;

class CBCStrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin|Admin');
        $this->middleware('permission:cbc_strands.view')->only(['index', 'show', 'substrands']);
        $this->middleware('permission:cbc_strands.manage')->except(['index', 'show', 'substrands']);
    }

    public function index(Request $request)
    {
        $query = CBCStrand::withCount('substrands');

        if ($request->filled('learning_area')) {
            $query->where('learning_area', $request->learning_area);
        }
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $strands = $query->ordered()->paginate(20)->withQueryString();

        $learningAreas = CBCStrand::distinct()->pluck('learning_area')->sort();
        $levels = CBCStrand::distinct()->pluck('level')->sort();

        return view('academics.cbc_strands.index', compact('strands', 'learningAreas', 'levels'));
    }

    public function create()
    {
        return view('academics.cbc_strands.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:cbc_strands,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'learning_area' => 'required|string|max:255',
            'level' => 'required|string|max:50',
            'display_order' => 'nullable|integer|min:0',
        ]);

        CBCStrand::create($validated);

        return redirect()
            ->route('academics.cbc-strands.index')
            ->with('success', 'CBC Strand created successfully.');
    }

    public function show(CBCStrand $cbc_strand)
    {
        $cbc_strand->load('substrands');
        return view('academics.cbc_strands.show', compact('cbc_strand'));
    }

    public function edit(CBCStrand $cbc_strand)
    {
        return view('academics.cbc_strands.edit', compact('cbc_strand'));
    }

    public function update(Request $request, CBCStrand $cbc_strand)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:cbc_strands,code,' . $cbc_strand->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'learning_area' => 'required|string|max:255',
            'level' => 'required|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $cbc_strand->update($validated);

        return redirect()
            ->route('academics.cbc-strands.index')
            ->with('success', 'CBC Strand updated successfully.');
    }

    public function destroy(CBCStrand $cbc_strand)
    {
        if ($cbc_strand->substrands()->count() > 0) {
            return back()->with('error', 'Cannot delete strand with existing substrands.');
        }

        $cbc_strand->delete();

        return redirect()
            ->route('academics.cbc-strands.index')
            ->with('success', 'CBC Strand deleted successfully.');
    }

    public function substrands(CBCStrand $cbc_strand)
    {
        $substrands = $cbc_strand->substrands()->ordered()->get();
        return view('academics.cbc_strands.substrands', compact('cbc_strand', 'substrands'));
    }
}
