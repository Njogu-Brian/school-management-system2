<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\CustomDeduction;
use App\Models\PayrollRecord;
use App\Models\PayrollPeriod;
use App\Models\SalaryHistory;
use App\Models\StaffAdvance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $record = PayrollRecord::with('payrollPeriod')->findOrFail($id);

        if (!$record->canEdit()) {
            return back()->with('error', 'This payroll record cannot be edited.');
        }

        $validated = $request->validate([
            'bonus' => 'nullable|numeric|min:0',
            'advance_deduction' => 'nullable|numeric|min:0',
            'custom_deductions_total' => 'nullable|numeric|min:0',
            'adjustments_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $record->bonus = $validated['bonus'] ?? 0;
        $record->advance_deduction = $validated['advance_deduction'] ?? 0;
        $record->custom_deductions_total = $validated['custom_deductions_total'] ?? 0;
        $record->adjustments_notes = $validated['adjustments_notes'] ?? null;
        $record->notes = $validated['notes'] ?? null;
        $record->calculateTotals();
        $record->save();

        SalaryHistory::where('payroll_record_id', $record->id)->update([
            'gross_salary' => $record->gross_salary,
            'total_deductions' => $record->total_deductions,
            'net_salary' => $record->net_salary,
        ]);

        $period = $record->payrollPeriod;
        $period->load('payrollRecords');
        $period->calculateTotals();
        $period->save();

        return back()->with('success', 'Payroll record updated successfully.');
    }

    /**
     * Reject/cancel a single staff payslip without wiping the period.
     */
    public function cancel(Request $request, $id)
    {
        $record = PayrollRecord::with(['payrollPeriod', 'staff'])->findOrFail($id);

        if (! $record->canCancel()) {
            return back()->with('error', 'This payroll record cannot be cancelled. Unlock the period first if needed.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($record, $validated) {
                $this->reverseAppliedDeductions($record);

                $note = 'Cancelled'.($validated['reason'] ? ': '.$validated['reason'] : '').' by '.auth()->user()?->name.' on '.now()->format('Y-m-d H:i');
                $record->notes = trim(($record->notes ? $record->notes."\n" : '').$note);
                $record->status = 'cancelled';
                $record->save();

                SalaryHistory::where('payroll_record_id', $record->id)->delete();

                $period = $record->payrollPeriod;
                $period->load('payrollRecords');
                $period->calculateTotals();
                $period->save();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to cancel payroll record: '.$e->getMessage());
        }

        return redirect()->route('hr.payroll.records.show', $record->id)
            ->with('success', 'Payroll record cancelled. Staff is excluded from this period\'s pay totals. You can re-process the period later to regenerate their slip if needed.');
    }

    /**
     * Reverse advance repayments and custom deduction progress applied when the slip was generated.
     */
    private function reverseAppliedDeductions(PayrollRecord $record): void
    {
        $advanceAmount = (float) $record->advance_deduction;
        if ($advanceAmount > 0) {
            $remaining = $advanceAmount;
            $advances = StaffAdvance::where('staff_id', $record->staff_id)
                ->where('amount_repaid', '>', 0)
                ->orderByDesc('updated_at')
                ->get();

            foreach ($advances as $advance) {
                if ($remaining <= 0) {
                    break;
                }
                $reverse = min((float) $advance->amount_repaid, $remaining);
                $advance->reverseRepayment($reverse);
                $remaining -= $reverse;
            }
        }

        $breakdown = $record->custom_deductions_breakdown;
        if (is_array($breakdown) && $breakdown !== []) {
            foreach ($breakdown as $typeId => $amount) {
                $remaining = (float) $amount;
                if ($remaining <= 0) {
                    continue;
                }

                $deductions = CustomDeduction::where('staff_id', $record->staff_id)
                    ->where('deduction_type_id', $typeId)
                    ->where('amount_deducted', '>', 0)
                    ->orderByDesc('updated_at')
                    ->get();

                foreach ($deductions as $deduction) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $reverse = min((float) $deduction->amount_deducted, $remaining);
                    $deduction->reverseDeduction($reverse);
                    $remaining -= $reverse;
                }
            }
        } elseif ((float) $record->custom_deductions_total > 0) {
            $remaining = (float) $record->custom_deductions_total;
            $deductions = CustomDeduction::where('staff_id', $record->staff_id)
                ->where('amount_deducted', '>', 0)
                ->orderByDesc('updated_at')
                ->get();

            foreach ($deductions as $deduction) {
                if ($remaining <= 0) {
                    break;
                }
                $reverse = min((float) $deduction->amount_deducted, $remaining);
                $deduction->reverseDeduction($reverse);
                $remaining -= $reverse;
            }
        }
    }
}
