<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\LearningArea;
use App\Models\Academics\CBCStrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningAreaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:learning_areas.view')->only(['index', 'show']);
        $this->middleware('permission:learning_areas.create')->only(['create', 'store']);
        $this->middleware('permission:learning_areas.edit')->only(['edit', 'update']);
        $this->middleware('permission:learning_areas.delete')->only(['destroy']);
        $this->middleware('permission:learning_areas.manage')->only(['manage']);
    }

    public function index(Request $request)
    {
        $query = LearningArea::withCount('strands');

        // Filters
        if ($request->filled('level_category')) {
            $query->where('level_category', $request->level_category);
        }
        if ($request->filled('level')) {
            $query->whereJsonContains('levels', $request->level);
        }
        if ($request->filled('is_core')) {
            $query->where('is_core', $request->is_core == '1');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $learningAreas = $query->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $levelCategories = LearningArea::distinct()->pluck('level_category')->filter()->sort()->values();
        $levels = LearningArea::select('levels')->get()
            ->pluck('levels')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('academics.learning_areas.index', compact('learningAreas', 'levelCategories', 'levels'));
    }

    public function create()
    {
        $levelCategories = [
            'Pre-Primary' => 'Pre-Primary',
            'Lower Primary' => 'Lower Primary',
            'Upper Primary' => 'Upper Primary',
            'Junior Secondary' => 'Junior Secondary',
            'Senior Secondary' => 'Senior Secondary',
        ];

        $levels = [
            'Pre-Primary' => ['PP1', 'PP2'],
            'Lower Primary' => ['Grade 1', 'Grade 2', 'Grade 3'],
            'Upper Primary' => ['Grade 4', 'Grade 5', 'Grade 6'],
            'Junior Secondary' => ['Grade 7', 'Grade 8', 'Grade 9'],
            'Senior Secondary' => ['Form 1', 'Form 2', 'Form 3', 'Form 4'],
        ];

        return view('academics.learning_areas.create', compact('levelCategories', 'levels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:learning_areas,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level_category' => 'nullable|string|max:100',
            'levels' => 'nullable|array',
            'levels.*' => 'string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['is_core'] = $validated['is_core'] ?? true;

        LearningArea::create($validated);

        return redirect()
            ->route('academics.learning-areas.index')
            ->with('success', 'Learning Area created successfully.');
    }

    public function show(LearningArea $learning_area)
    {
        $learning_area->load(['strands.substrands.competencies']);
        
        $strands = $learning_area->strands()
            ->withCount(['substrands', 'competencies'])
            ->ordered()
            ->get();

        return view('academics.learning_areas.show', compact('learning_area', 'strands'));
    }

    public function edit(LearningArea $learning_area)
    {
        $levelCategories = [
            'Pre-Primary' => 'Pre-Primary',
            'Lower Primary' => 'Lower Primary',
            'Upper Primary' => 'Upper Primary',
            'Junior Secondary' => 'Junior Secondary',
            'Senior Secondary' => 'Senior Secondary',
        ];

        $levels = [
            'Pre-Primary' => ['PP1', 'PP2'],
            'Lower Primary' => ['Grade 1', 'Grade 2', 'Grade 3'],
            'Upper Primary' => ['Grade 4', 'Grade 5', 'Grade 6'],
            'Junior Secondary' => ['Grade 7', 'Grade 8', 'Grade 9'],
            'Senior Secondary' => ['Form 1', 'Form 2', 'Form 3', 'Form 4'],
        ];

        return view('academics.learning_areas.edit', compact('learning_area', 'levelCategories', 'levels'));
    }

    public function update(Request $request, LearningArea $learning_area)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:learning_areas,code,' . $learning_area->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'level_category' => 'nullable|string|max:100',
            'levels' => 'nullable|array',
            'levels.*' => 'string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $learning_area->update($validated);

        return redirect()
            ->route('academics.learning-areas.index')
            ->with('success', 'Learning Area updated successfully.');
    }

    public function destroy(LearningArea $learning_area)
    {
        if ($learning_area->strands()->count() > 0) {
            return back()->with('error', 'Cannot delete learning area with existing strands. Please delete or reassign strands first.');
        }

        $learning_area->delete();

        return redirect()
            ->route('academics.learning-areas.index')
            ->with('success', 'Learning Area deleted successfully.');
    }

    /**
     * Get strands for a learning area (AJAX)
     */
    public function getStrands(LearningArea $learning_area, Request $request)
    {
        $level = $request->get('level');
        
        $strands = $learning_area->strands()
            ->when($level, function($q) use ($level) {
                $q->where('level', $level);
            })
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(function($strand) {
                return [
                    'id' => $strand->id,
                    'name' => $strand->name,
                    'code' => $strand->code,
                    'level' => $strand->level,
                ];
            });

        return response()->json($strands);
    }
}

