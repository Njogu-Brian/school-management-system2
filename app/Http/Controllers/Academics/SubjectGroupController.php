<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\SubjectGroup;
use Illuminate\Http\Request;

class SubjectGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = SubjectGroup::withCount('subjects');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        } else {
            $query->where('is_active', true); // Default to active only
        }

        $groups = $query->ordered()->paginate(20)->withQueryString();

        return view('academics.subject_groups.index', compact('groups'));
    }

    public function create()
    {
        return view('academics.subject_groups.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subject_groups,name',
            'code' => 'nullable|string|max:50|unique:subject_groups,code',
            'display_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        SubjectGroup::create($validated);

        return redirect()
            ->route('academics.subject_groups.index')
            ->with('success', 'Subject group created successfully.');
    }

    public function edit(SubjectGroup $subject_group)
    {
        return view('academics.subject_groups.edit', compact('subject_group'));
    }

    public function update(Request $request, SubjectGroup $subject_group)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:subject_groups,name,' . $subject_group->id,
            'code' => 'nullable|string|max:50|unique:subject_groups,code,' . $subject_group->id,
            'display_order' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $subject_group->update($validated);

        return redirect()
            ->route('academics.subject_groups.index')
            ->with('success', 'Subject group updated successfully.');
    }

    public function destroy(SubjectGroup $subject_group)
    {
        // Check if group has subjects
        if ($subject_group->subjects()->count() > 0) {
            return back()
                ->with('error', 'Cannot delete subject group with existing subjects. Remove or reassign subjects first.');
        }

        $subject_group->delete();

        return redirect()
            ->route('academics.subject_groups.index')
            ->with('success', 'Subject group deleted successfully.');
    }
}
