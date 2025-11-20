<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\StaffAdvance;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffAdvanceController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffAdvance::with(['staff', 'approvedBy', 'createdBy']);

        if ($request->filled('staff_id')) {
            $query->where('staff_id', $request->staff_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $advances = $query->orderBy('created_at', 'desc')->paginate(20);
        $staff = Staff::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get();

        return view('hr.payroll.advances.index', compact('advances', 'staff'));
    }

    public function create()
    {
        $staff = Staff::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get();
        return view('hr.payroll.advances.create', compact('staff'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'advance_date' => 'required|date',
            'repayment_method' => 'required|in:lump_sum,installments,monthly_deduction',
            'installment_count' => 'nullable|integer|min:1|required_if:repayment_method,installments',
            'monthly_deduction_amount' => 'nullable|numeric|min:0.01|required_if:repayment_method,monthly_deduction',
            'expected_completion_date' => 'nullable|date|after:advance_date',
            'notes' => 'nullable|string',
        ]);

        $validated['balance'] = $validated['amount'];
        $validated['amount_repaid'] = 0;
        $validated['status'] = 'pending';
        $validated['created_by'] = auth()->id();

        // Calculate expected completion date if not provided
        if ($validated['repayment_method'] === 'monthly_deduction' && !$request->filled('expected_completion_date')) {
            $months = ceil($validated['amount'] / $validated['monthly_deduction_amount']);
            $validated['expected_completion_date'] = Carbon::parse($validated['advance_date'])->addMonths($months);
        }

        $advance = StaffAdvance::create($validated);

        return redirect()->route('hr.payroll.advances.show', $advance->id)
            ->with('success', 'Staff advance created successfully.');
    }

    public function show($id)
    {
        $advance = StaffAdvance::with(['staff', 'approvedBy', 'createdBy', 'customDeductions.deductionType'])->findOrFail($id);
        return view('hr.payroll.advances.show', compact('advance'));
    }

    public function edit($id)
    {
        $advance = StaffAdvance::findOrFail($id);
        
        if ($advance->status !== 'pending') {
            return back()->with('error', 'Only pending advances can be edited.');
        }

        $staff = Staff::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get();
        return view('hr.payroll.advances.edit', compact('advance', 'staff'));
    }

    public function update(Request $request, $id)
    {
        $advance = StaffAdvance::findOrFail($id);

        if ($advance->status !== 'pending') {
            return back()->with('error', 'Only pending advances can be edited.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'advance_date' => 'required|date',
            'repayment_method' => 'required|in:lump_sum,installments,monthly_deduction',
            'installment_count' => 'nullable|integer|min:1|required_if:repayment_method,installments',
            'monthly_deduction_amount' => 'nullable|numeric|min:0.01|required_if:repayment_method,monthly_deduction',
            'expected_completion_date' => 'nullable|date|after:advance_date',
            'notes' => 'nullable|string',
        ]);

        $validated['balance'] = $validated['amount'] - $advance->amount_repaid;

        $advance->update($validated);

        return redirect()->route('hr.payroll.advances.show', $advance->id)
            ->with('success', 'Staff advance updated successfully.');
    }

    public function approve(Request $request, $id)
    {
        $advance = StaffAdvance::findOrFail($id);

        if ($advance->status !== 'pending') {
            return back()->with('error', 'Only pending advances can be approved.');
        }

        $advance->status = 'approved';
        $advance->approved_by = auth()->id();
        $advance->approved_at = now();
        
        // If monthly deduction, create custom deduction
        if ($advance->repayment_method === 'monthly_deduction' && $advance->monthly_deduction_amount) {
            // Get or create loan deduction type
            $loanType = \App\Models\DeductionType::firstOrCreate(
                ['code' => 'LOAN'],
                [
                    'name' => 'Loan Repayment',
                    'calculation_method' => 'fixed_amount',
                    'is_active' => true,
                    'is_statutory' => false,
                ]
            );

            \App\Models\CustomDeduction::create([
                'staff_id' => $advance->staff_id,
                'deduction_type_id' => $loanType->id,
                'staff_advance_id' => $advance->id,
                'amount' => $advance->monthly_deduction_amount,
                'effective_from' => Carbon::now()->startOfMonth(),
                'frequency' => 'monthly',
                'total_amount' => $advance->amount,
                'status' => 'active',
                'description' => "Loan repayment for advance #{$advance->id}",
                'created_by' => auth()->id(),
            ]);
        }

        $advance->status = 'active';
        $advance->save();

        return back()->with('success', 'Advance approved and activated successfully.');
    }

    public function recordRepayment(Request $request, $id)
    {
        $advance = StaffAdvance::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $advance->balance,
            'notes' => 'nullable|string',
        ]);

        $advance->recordRepayment($validated['amount']);

        if ($request->filled('notes')) {
            $advance->notes = ($advance->notes ? $advance->notes . "\n\n" : '') . 
                Carbon::now()->format('Y-m-d') . ': ' . $validated['notes'];
            $advance->save();
        }

        return back()->with('success', 'Repayment recorded successfully.');
    }

    public function destroy($id)
    {
        $advance = StaffAdvance::findOrFail($id);

        if ($advance->status !== 'pending') {
            return back()->with('error', 'Only pending advances can be deleted.');
        }

        $advance->delete();

        return redirect()->route('hr.payroll.advances.index')
            ->with('success', 'Staff advance deleted successfully.');
    }
}
