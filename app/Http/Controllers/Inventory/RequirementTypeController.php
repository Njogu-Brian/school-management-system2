<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\RequirementType;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class RequirementTypeController extends Controller
{
    public function index()
    {
        $types = RequirementType::orderBy('name')->get();
        return view('inventory.requirement-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:requirement_types,name',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $type = RequirementType::create($validated);

        ActivityLog::log('create', $type, "Created requirement type: {$type->name}");

        return back()->with('success', 'Requirement type created successfully.');
    }

    public function update(Request $request, RequirementType $type)
    {
        $oldValues = $type->toArray();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:requirement_types,name,' . $type->id,
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $type->update($validated);

        ActivityLog::log('update', $type, "Updated requirement type: {$type->name}", $oldValues, $type->toArray());

        return back()->with('success', 'Requirement type updated successfully.');
    }

    public function destroy(RequirementType $type)
    {
        $name = $type->name;
        $type->delete();

        ActivityLog::log('delete', null, "Deleted requirement type: {$name}");

        return back()->with('success', 'Requirement type deleted successfully.');
    }
}
