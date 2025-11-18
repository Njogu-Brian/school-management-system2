<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\CBCSubstrand;
use App\Models\Academics\CBCStrand;
use Illuminate\Http\Request;

class CBCSubstrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:Super Admin|Admin');
        $this->middleware('permission:cbc_strands.manage');
    }

    public function index(Request $request)
    {
        $query = CBCSubstrand::with('strand.learningArea');

        if ($request->filled('strand_id')) {
            $query->where('strand_id', $request->strand_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $substrands = $query->orderBy('display_order')->orderBy('name')->paginate(20)->withQueryString();
        $strands = CBCStrand::where('is_active', true)->orderBy('display_order')->orderBy('name')->get();

        return view('academics.cbc_substrands.index', compact('substrands', 'strands'));
    }

    public function create()
    {
        $strands = CBCStrand::with('learningArea')->where('is_active', true)->orderBy('display_order')->orderBy('name')->get();
        return view('academics.cbc_substrands.create', compact('strands'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'strand_id' => 'required|exists:cbc_strands,id',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'learning_outcomes' => 'nullable|array',
            'learning_outcomes.*' => 'nullable|string',
            'key_inquiry_questions' => 'nullable|array',
            'key_inquiry_questions.*' => 'nullable|string',
            'core_competencies' => 'nullable|array',
            'core_competencies.*' => 'nullable|string',
            'values' => 'nullable|array',
            'values.*' => 'nullable|string',
            'pclc' => 'nullable|array',
            'pclc.*' => 'nullable|string',
            'suggested_lessons' => 'nullable|integer|min:1',
            'display_order' => 'nullable|integer|min:0',
        ]);

        // Filter out empty values from arrays
        foreach (['learning_outcomes', 'key_inquiry_questions', 'core_competencies', 'values', 'pclc'] as $field) {
            if (isset($validated[$field]) && is_array($validated[$field])) {
                $validated[$field] = array_values(array_filter($validated[$field], fn($v) => !empty(trim($v ?? ''))));
                if (empty($validated[$field])) {
                    $validated[$field] = null;
                }
            }
        }

        CBCSubstrand::create($validated);

        return redirect()
            ->route('academics.cbc-substrands.index')
            ->with('success', 'CBC Substrand created successfully.');
    }

    public function show(CBCSubstrand $cbc_substrand)
    {
        $cbc_substrand->load('strand');
        return view('academics.cbc_substrands.show', compact('cbc_substrand'));
    }

    public function edit(CBCSubstrand $cbc_substrand)
    {
        $strands = CBCStrand::with('learningArea')->where('is_active', true)->orderBy('display_order')->orderBy('name')->get();
        return view('academics.cbc_substrands.edit', compact('cbc_substrand', 'strands'));
    }

    public function update(Request $request, CBCSubstrand $cbc_substrand)
    {
        $validated = $request->validate([
            'strand_id' => 'required|exists:cbc_strands,id',
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'learning_outcomes' => 'nullable|array',
            'learning_outcomes.*' => 'nullable|string',
            'key_inquiry_questions' => 'nullable|array',
            'key_inquiry_questions.*' => 'nullable|string',
            'core_competencies' => 'nullable|array',
            'core_competencies.*' => 'nullable|string',
            'values' => 'nullable|array',
            'values.*' => 'nullable|string',
            'pclc' => 'nullable|array',
            'pclc.*' => 'nullable|string',
            'suggested_lessons' => 'nullable|integer|min:1',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Filter out empty values from arrays
        foreach (['learning_outcomes', 'key_inquiry_questions', 'core_competencies', 'values', 'pclc'] as $field) {
            if (isset($validated[$field]) && is_array($validated[$field])) {
                $validated[$field] = array_values(array_filter($validated[$field], fn($v) => !empty(trim($v ?? ''))));
                if (empty($validated[$field])) {
                    $validated[$field] = null;
                }
            }
        }

        $cbc_substrand->update($validated);

        return redirect()
            ->route('academics.cbc-substrands.index')
            ->with('success', 'CBC Substrand updated successfully.');
    }

    public function destroy(CBCSubstrand $cbc_substrand)
    {
        if ($cbc_substrand->lessonPlans()->count() > 0) {
            return back()->with('error', 'Cannot delete substrand with existing lesson plans.');
        }

        $cbc_substrand->delete();

        return redirect()
            ->route('academics.cbc-substrands.index')
            ->with('success', 'CBC Substrand deleted successfully.');
    }
}
