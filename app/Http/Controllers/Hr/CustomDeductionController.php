<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\CustomDeduction;
use App\Models\DeductionType;
use App\Models\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CustomDeductionController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomDeduction::with(['staff', 'deductionType', 'staffAdvance']);

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('deduction_type_id')) {
            $query->where('deduction_type_id', $request->deduction_type_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }

        $deductions = $query->orderBy('created_at', 'desc')->paginate(20);
        $staff = Staff::where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $types = DeductionType::where('is_active', true)->orderBy('name')->get();

        return view('hr.payroll.custom-deductions.index', compact('deductions', 'staff', 'types'));
    }

    public function create()
    {
        $staff = Staff::where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $types = DeductionType::where('is_active', true)->where('is_statutory', false)->orderBy('name')->get();
        $advances = \App\Models\StaffAdvance::where('status', 'active')
            ->where('balance', '>', 0)
            ->with('staff')
            ->get();

        return view('hr.payroll.custom-deductions.create', compact('staff', 'types', 'advances'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'deduction_type_id' => 'required|exists:deduction_types,id',
            'staff_advance_id' => 'nullable|exists:staff_advances,id',
            'amount' => 'required|numeric|min:0.01',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'frequency' => 'required|in:one_time,monthly,quarterly,yearly',
            'total_installments' => 'nullable|integer|min:1',
            'total_amount' => 'nullable|numeric|min:0.01',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['installment_number'] = 0;
        $validated['amount_deducted'] = 0;
        $validated['status'] = 'active';
        $validated['created_by'] = auth()->id();

        // If total_amount and total_installments provided, calculate per-installment amount
        if ($request->filled('total_amount') && $request->filled('total_installments')) {
            $validated['amount'] = $validated['total_amount'] / $validated['total_installments'];
        }

        $deduction = CustomDeduction::create($validated);

        return redirect()->route('hr.payroll.custom-deductions.show', $deduction->id)
            ->with('success', 'Custom deduction created successfully.');
    }

    public function show($id)
    {
        $deduction = CustomDeduction::with(['staff', 'deductionType', 'staffAdvance', 'createdBy'])->findOrFail($id);
        return view('hr.payroll.custom-deductions.show', compact('deduction'));
    }

    public function edit($id)
    {
        $deduction = CustomDeduction::findOrFail($id);

        if ($deduction->status !== 'active') {
            return back()->with('error', 'Only active deductions can be edited.');
        }

        $staff = Staff::where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $types = DeductionType::where('is_active', true)->where('is_statutory', false)->orderBy('name')->get();
        $advances = \App\Models\StaffAdvance::where('status', 'active')
            ->where('balance', '>', 0)
            ->with('staff')
            ->get();

        return view('hr.payroll.custom-deductions.edit', compact('deduction', 'staff', 'types', 'advances'));
    }

    public function update(Request $request, $id)
    {
        $deduction = CustomDeduction::findOrFail($id);

        if ($deduction->status !== 'active') {
            return back()->with('error', 'Only active deductions can be edited.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after:effective_from',
            'frequency' => 'required|in:one_time,monthly,quarterly,yearly',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $deduction->update($validated);

        return redirect()->route('hr.payroll.custom-deductions.show', $deduction->id)
            ->with('success', 'Custom deduction updated successfully.');
    }

    public function suspend($id)
    {
        $deduction = CustomDeduction::findOrFail($id);

        if ($deduction->status !== 'active') {
            return back()->with('error', 'Only active deductions can be suspended.');
        }

        $deduction->status = 'suspended';
        $deduction->save();

        return back()->with('success', 'Custom deduction suspended successfully.');
    }

    public function activate($id)
    {
        $deduction = CustomDeduction::findOrFail($id);

        if ($deduction->status !== 'suspended') {
            return back()->with('error', 'Only suspended deductions can be activated.');
        }

        $deduction->status = 'active';
        $deduction->save();

        return back()->with('success', 'Custom deduction activated successfully.');
    }

    public function destroy($id)
    {
        $deduction = CustomDeduction::findOrFail($id);

        if ($deduction->status === 'active' && $deduction->amount_deducted > 0) {
            return back()->with('error', 'Cannot delete deduction that has been applied to payroll.');
        }

        $deduction->delete();

        return redirect()->route('hr.payroll.custom-deductions.index')
            ->with('success', 'Custom deduction deleted successfully.');
    }
}
