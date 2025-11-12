<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;

class PayrollRecordController extends Controller
{
    /**
     * Display payroll records for a period
     */
    public function index(Request $request)
    {
        $query = PayrollRecord::with(['staff', 'payrollPeriod', 'salaryStructure']);

        if ($request->filled('payroll_period_id')) {
            $query->where('payroll_period_id', $request->payroll_period_id);
        }

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(20);
        $periods = PayrollPeriod::orderBy('year', 'desc')->orderBy('month', 'desc')->get();

        return view('hr.payroll.records.index', compact('records', 'periods'));
    }

    /**
     * Show a specific payroll record
     */
    public function show($id)
    {
        $record = PayrollRecord::with(['staff', 'payrollPeriod', 'salaryStructure', 'createdBy'])->findOrFail($id);
        return view('hr.payroll.records.show', compact('record'));
    }

    /**
     * Update payroll record (adjustments)
     */
    public function update(Request $request, $id)
    {
        $record = PayrollRecord::findOrFail($id);

        if (!$record->canEdit()) {
            return back()->with('error', 'This payroll record cannot be edited.');
        }

        $validated = $request->validate([
            'bonus' => 'nullable|numeric|min:0',
            'advance_deduction' => 'nullable|numeric|min:0',
            'custom_deductions_total' => 'nullable|numeric|min:0',
            'adjustments_notes' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $record->fill($validated);
        $record->calculateTotals();
        $record->save();

        return back()->with('success', 'Payroll record updated successfully.');
    }
}
