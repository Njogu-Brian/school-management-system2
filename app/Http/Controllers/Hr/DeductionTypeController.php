<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\DeductionType;
use Illuminate\Http\Request;

class DeductionTypeController extends Controller
{
    public function index()
    {
        $types = DeductionType::with('createdBy')
            ->orderBy('is_statutory', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return view('hr.payroll.deduction-types.index', compact('types'));
    }

    public function create()
    {
        return view('hr.payroll.deduction-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:deduction_types,code',
            'description' => 'nullable|string',
            'calculation_method' => 'required|in:fixed_amount,percentage_of_basic,percentage_of_gross,custom',
            'default_amount' => 'nullable|numeric|min:0|required_if:calculation_method,fixed_amount',
            'percentage' => 'nullable|numeric|min:0|max:100|required_if:calculation_method,percentage_of_basic|required_if:calculation_method,percentage_of_gross',
            'is_active' => 'boolean',
            'is_statutory' => 'boolean',
            'requires_approval' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $request->has('is_active');
        $validated['is_statutory'] = $request->has('is_statutory');
        $validated['requires_approval'] = $request->has('requires_approval');

        $type = DeductionType::create($validated);

        return redirect()->route('hr.payroll.deduction-types.show', $type->id)
            ->with('success', 'Deduction type created successfully.');
    }

    public function show($id)
    {
        $type = DeductionType::with(['createdBy', 'customDeductions.staff'])->findOrFail($id);
        return view('hr.payroll.deduction-types.show', compact('type'));
    }

    public function edit($id)
    {
        $type = DeductionType::findOrFail($id);
        return view('hr.payroll.deduction-types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $type = DeductionType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:deduction_types,code,' . $id,
            'description' => 'nullable|string',
            'calculation_method' => 'required|in:fixed_amount,percentage_of_basic,percentage_of_gross,custom',
            'default_amount' => 'nullable|numeric|min:0|required_if:calculation_method,fixed_amount',
            'percentage' => 'nullable|numeric|min:0|max:100|required_if:calculation_method,percentage_of_basic|required_if:calculation_method,percentage_of_gross',
            'is_active' => 'boolean',
            'is_statutory' => 'boolean',
            'requires_approval' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_statutory'] = $request->has('is_statutory');
        $validated['requires_approval'] = $request->has('requires_approval');

        $type->update($validated);

        return redirect()->route('hr.payroll.deduction-types.show', $type->id)
            ->with('success', 'Deduction type updated successfully.');
    }

    public function destroy($id)
    {
        $type = DeductionType::findOrFail($id);

        if ($type->is_statutory) {
            return back()->with('error', 'Statutory deduction types cannot be deleted.');
        }

        if ($type->customDeductions()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete deduction type with active deductions.');
        }

        $type->delete();

        return redirect()->route('hr.payroll.deduction-types.index')
            ->with('success', 'Deduction type deleted successfully.');
    }
}
