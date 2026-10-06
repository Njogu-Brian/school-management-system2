<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\StaffAdvance;
use App\Models\Staff;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffAdvanceController extends Controller
{
    public function index(Request $request)
    {
        $query = StaffAdvance::with(['staff', 'sourceStaff', 'approvedBy', 'createdBy']);

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
        $validated = $request->validate($this->advanceRules());

        if (
            ($validated['source_type'] ?? 'company') === 'staff'
            && (int) ($validated['source_staff_id'] ?? 0) === (int) $validated['staff_id']
        ) {
            return back()->withInput()->withErrors([
                'source_staff_id' => 'Funding staff cannot be the same as the advance recipient.',
            ]);
        }

        $validated['balance'] = $validated['amount'];
        $validated['amount_repaid'] = 0;
        $validated['status'] = 'pending';
        $validated['created_by'] = auth()->id();
        $validated['requested_amount'] = $validated['amount'];
        $validated['source_type'] = $validated['source_type'] ?? 'company';
        if ($validated['source_type'] !== 'staff') {
            $validated['source_staff_id'] = null;
        }

        if ($validated['repayment_method'] === 'lump_sum') {
            $validated['installment_count'] = 1;
        }

        if ($validated['repayment_method'] === 'monthly_deduction' && empty($validated['expected_completion_date'])) {
            $months = ceil($validated['amount'] / $validated['monthly_deduction_amount']);
            $validated['expected_completion_date'] = Carbon::parse($validated['advance_date'])->addMonths($months);
        }

        $advance = StaffAdvance::create($validated);

        return redirect()->route('hr.payroll.advances.show', $advance->id)
            ->with('success', 'Staff advance created successfully.');
    }

    public function show($id)
    {
        $advance = StaffAdvance::with([
            'staff',
            'sourceStaff',
            'approvedBy',
            'createdBy',
            'customDeductions.deductionType',
            'installments',
        ])->findOrFail($id);
        $staff = Staff::where('status', 'active')->orderBy('first_name')->orderBy('last_name')->get();

        return view('hr.payroll.advances.show', compact('advance', 'staff'));
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

        $validated = $request->validate($this->advanceRules(false));
        unset($validated['staff_id']);

        if (
            ($validated['source_type'] ?? 'company') === 'staff'
            && (int) ($validated['source_staff_id'] ?? 0) === (int) $advance->staff_id
        ) {
            return back()->withInput()->withErrors([
                'source_staff_id' => 'Funding staff cannot be the same as the advance recipient.',
            ]);
        }

        $validated['balance'] = $validated['amount'] - $advance->amount_repaid;
        $validated['source_type'] = $validated['source_type'] ?? 'company';
        if ($validated['source_type'] !== 'staff') {
            $validated['source_staff_id'] = null;
        }
        if ($validated['repayment_method'] === 'lump_sum') {
            $validated['installment_count'] = 1;
        }

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

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.01',
            'repayment_method' => 'nullable|in:lump_sum,installments,monthly_deduction',
            'installment_count' => 'nullable|integer|min:1',
            'monthly_deduction_amount' => 'nullable|numeric|min:0.01',
            'source_type' => 'required|in:company,staff',
            'source_staff_id' => 'nullable|exists:staff,id|required_if:source_type,staff',
            'repayment_start_year' => 'required|integer|min:2000|max:2100',
            'repayment_start_month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string',
        ]);

        if (! $advance->requested_amount) {
            $advance->requested_amount = $advance->amount;
        }

        if (isset($validated['amount'])) {
            $advance->amount = (float) $validated['amount'];
            $advance->balance = $advance->amount - (float) $advance->amount_repaid;
        }
        if (! empty($validated['repayment_method'])) {
            $advance->repayment_method = $validated['repayment_method'];
        }
        if (array_key_exists('installment_count', $validated)) {
            $advance->installment_count = $validated['installment_count'];
        }
        if (array_key_exists('monthly_deduction_amount', $validated)) {
            $advance->monthly_deduction_amount = $validated['monthly_deduction_amount'];
        }

        if (
            $validated['source_type'] === 'staff'
            && (int) ($validated['source_staff_id'] ?? 0) === (int) $advance->staff_id
        ) {
            return back()->withInput()->withErrors([
                'source_staff_id' => 'Funding staff cannot be the same as the advance recipient.',
            ]);
        }

        $advance->source_type = $validated['source_type'];
        $advance->source_staff_id = $validated['source_type'] === 'staff'
            ? $validated['source_staff_id']
            : null;
        $advance->repayment_start_year = $validated['repayment_start_year'];
        $advance->repayment_start_month = $validated['repayment_start_month'];

        if ($advance->repayment_method === 'lump_sum') {
            $advance->installment_count = 1;
        }

        if (! empty($validated['notes'])) {
            $advance->notes = trim(($advance->notes ? $advance->notes."\n" : '').$validated['notes']);
        }

        $advance->approved_by = auth()->id();
        $advance->approved_at = now();
        $advance->status = 'active';
        $advance->save();

        $advance->generateInstallmentSchedule();

        return back()->with('success', 'Advance approved and activated successfully.');
    }

    public function recordRepayment(Request $request, $id)
    {
        $advance = StaffAdvance::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$advance->balance,
            'notes' => 'nullable|string',
        ]);

        $advance->recordRepayment($validated['amount']);

        if ($request->filled('notes')) {
            $advance->notes = ($advance->notes ? $advance->notes."\n\n" : '')
                .Carbon::now()->format('Y-m-d').': '.$validated['notes'];
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

    protected function advanceRules(bool $requireStaff = true): array
    {
        return [
            'staff_id' => ($requireStaff ? 'required' : 'nullable').'|exists:staff,id',
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'advance_date' => 'required|date',
            'repayment_method' => 'required|in:lump_sum,installments,monthly_deduction',
            'installment_count' => 'nullable|integer|min:1|required_if:repayment_method,installments',
            'monthly_deduction_amount' => 'nullable|numeric|min:0.01|required_if:repayment_method,monthly_deduction',
            'source_type' => 'required|in:company,staff',
            'source_staff_id' => 'nullable|exists:staff,id|required_if:source_type,staff',
            'repayment_start_year' => 'required|integer|min:2000|max:2100',
            'repayment_start_month' => 'required|integer|min:1|max:12',
            'expected_completion_date' => 'nullable|date|after:advance_date',
            'notes' => 'nullable|string',
        ];
    }
}
