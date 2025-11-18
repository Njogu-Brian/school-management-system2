<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Competency;
use App\Models\Academics\CBCSubstrand;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\LearningArea;
use Illuminate\Http\Request;

class CompetencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:competencies.view')->only(['index', 'show']);
        $this->middleware('permission:competencies.create')->only(['create', 'store']);
        $this->middleware('permission:competencies.edit')->only(['edit', 'update']);
        $this->middleware('permission:competencies.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Competency::with(['substrand.strand.learningArea']);

        // Filters
        if ($request->filled('substrand_id')) {
            $query->where('substrand_id', $request->substrand_id);
        }
        if ($request->filled('strand_id')) {
            $query->whereHas('substrand', function($q) use ($request) {
                $q->where('strand_id', $request->strand_id);
            });
        }
        if ($request->filled('learning_area_id')) {
            $query->whereHas('substrand.strand', function($q) use ($request) {
                $q->where('learning_area_id', $request->learning_area_id);
            });
        }
        if ($request->filled('competency_level')) {
            $query->where('competency_level', $request->competency_level);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $competencies = $query->where('is_active', true)->orderBy('display_order')->orderBy('name')->paginate(20)->withQueryString();

        $learningAreas = LearningArea::where('is_active', true)->orderBy('display_order')->orderBy('name')->get();
        $strands = CBCStrand::where('is_active', true)->orderBy('display_order')->orderBy('name')->get();
        $substrands = CBCSubstrand::where('is_active', true)->with('strand')->orderBy('display_order')->orderBy('name')->get();
        $competencyLevels = Competency::distinct()->pluck('competency_level')->filter()->sort()->values();

        return view('academics.competencies.index', compact(
            'competencies', 
            'learningAreas', 
            'strands', 
            'substrands', 
            'competencyLevels'
        ));
    }

    public function create(Request $request)
    {
        $learningAreas = LearningArea::where('is_active', true)->orderBy('display_order')->orderBy('name')->get();
        $substrandId = $request->get('substrand_id');
        $selectedSubstrand = $substrandId ? CBCSubstrand::with('strand.learningArea')->find($substrandId) : null;

        // Get all substrands for filtering
        $substrands = CBCSubstrand::where('is_active', true)
            ->with('strand.learningArea')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $competencyLevels = [
            'Basic' => 'Basic',
            'Intermediate' => 'Intermediate',
            'Advanced' => 'Advanced',
        ];

        return view('academics.competencies.create', compact(
            'learningAreas',
            'selectedSubstrand',
            'substrands',
            'competencyLevels'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'substrand_id' => 'required|exists:cbc_substrands,id',
            'code' => 'required|string|max:50|unique:competencies,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'indicators' => 'nullable|array',
            'indicators.*' => 'string|max:500',
            'assessment_criteria' => 'nullable|array',
            'assessment_criteria.*' => 'string|max:500',
            'competency_level' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        Competency::create($validated);

        return redirect()
            ->route('academics.competencies.index')
            ->with('success', 'Competency created successfully.');
    }

    public function show(Competency $competency)
    {
        $competency->load(['substrand.strand.learningArea']);
        return view('academics.competencies.show', compact('competency'));
    }

    public function edit(Competency $competency)
    {
        $competency->load(['substrand.strand.learningArea']);
        $learningAreas = LearningArea::where('is_active', true)->orderBy('display_order')->orderBy('name')->get();
        
        // Get all substrands for filtering
        $substrands = CBCSubstrand::where('is_active', true)
            ->with('strand.learningArea')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $competencyLevels = [
            'Basic' => 'Basic',
            'Intermediate' => 'Intermediate',
            'Advanced' => 'Advanced',
        ];

        return view('academics.competencies.edit', compact(
            'competency',
            'learningAreas',
            'substrands',
            'competencyLevels'
        ));
    }

    public function update(Request $request, Competency $competency)
    {
        $validated = $request->validate([
            'substrand_id' => 'required|exists:cbc_substrands,id',
            'code' => 'required|string|max:50|unique:competencies,code,' . $competency->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'indicators' => 'nullable|array',
            'indicators.*' => 'string|max:500',
            'assessment_criteria' => 'nullable|array',
            'assessment_criteria.*' => 'string|max:500',
            'competency_level' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $competency->update($validated);

        return redirect()
            ->route('academics.competencies.index')
            ->with('success', 'Competency updated successfully.');
    }

    public function destroy(Competency $competency)
    {
        $competency->delete();

        return redirect()
            ->route('academics.competencies.index')
            ->with('success', 'Competency deleted successfully.');
    }

    /**
     * Get competencies for a substrand (AJAX)
     */
    public function getBySubstrand(Request $request)
    {
        $substrandId = $request->get('substrand_id');
        
        if (!$substrandId) {
            return response()->json([]);
        }

        $competencies = Competency::where('substrand_id', $substrandId)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(function($competency) {
                return [
                    'id' => $competency->id,
                    'code' => $competency->code,
                    'name' => $competency->name,
                    'level' => $competency->competency_level,
                ];
            });

        return response()->json($competencies);
    }

    /**
     * Get competencies for a strand (AJAX)
     */
    public function getByStrand(Request $request)
    {
        $strandId = $request->get('strand_id');
        $learningAreaId = $request->get('learning_area_id');
        
        if (!$strandId && !$learningAreaId) {
            return response()->json([]);
        }

        $query = Competency::query();
        
        if ($strandId) {
            $query->whereHas('substrand', function($q) use ($strandId) {
                $q->where('strand_id', $strandId);
            });
        } elseif ($learningAreaId) {
            $query->whereHas('substrand.strand', function($q) use ($learningAreaId) {
                $q->where('learning_area_id', $learningAreaId);
            });
        }
        
        $competencies = $query->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->with(['substrand.strand'])
            ->get()
            ->map(function($competency) {
                return [
                    'id' => $competency->id,
                    'code' => $competency->code,
                    'name' => $competency->name,
                    'substrand' => $competency->substrand->name ?? '',
                    'level' => $competency->competency_level,
                ];
            });

        return response()->json($competencies);
    }
}

