<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\StaffAdvance;
use App\Models\StaffAdvanceInstallment;
use Illuminate\Support\Collection;

class StaffAdvancePayrollService
{
    /**
     * Reverse installment collections linked to a payroll record (borrower and/or funder).
     *
     * @return float Amount of borrower advance deduction reversed via installment rows
     */
    public function reverseForRecord(PayrollRecord $record): float
    {
        $reversedViaInstallments = 0.0;

        $borrowerInstallments = StaffAdvanceInstallment::with('advance')
            ->where('borrower_payroll_record_id', $record->id)
            ->get();

        foreach ($borrowerInstallments as $installment) {
            $amount = (float) $installment->amount;
            $reversedViaInstallments += $amount;
            if ($installment->advance) {
                $installment->advance->reverseRepayment($amount);
            }

            // Remove matching reimbursement from the funder's slip if still present.
            if ($installment->funder_payroll_record_id) {
                $funderRecord = PayrollRecord::find($installment->funder_payroll_record_id);
                if ($funderRecord && (int) $funderRecord->id !== (int) $record->id) {
                    $funderRecord->advance_reimbursement = max(
                        0,
                        round((float) $funderRecord->advance_reimbursement - $amount, 2)
                    );
                    $funderRecord->calculateTotals();
                    $funderRecord->save();
                }
            }

            $installment->status = 'pending';
            $installment->borrower_payroll_record_id = null;
            $installment->funder_payroll_record_id = null;
            $installment->save();
        }

        // This slip was the funder credit side only (or also) — clear the link and zero local reimbursement.
        StaffAdvanceInstallment::where('funder_payroll_record_id', $record->id)
            ->update(['funder_payroll_record_id' => null]);

        $record->advance_deduction = 0;
        $record->advance_reimbursement = 0;

        return $reversedViaInstallments;
    }

    /**
     * Apply scheduled installments for a period onto existing payroll records.
     * Records must already be saved (need IDs). Recalculates totals for touched slips.
     *
     * @param  Collection<int, PayrollRecord>|null  $records
     */
    public function applyForPeriod(PayrollPeriod $period, ?Collection $records = null): void
    {
        $records = $records ?? PayrollRecord::where('payroll_period_id', $period->id)
            ->whereIn('status', ['draft', 'approved'])
            ->get();

        $byStaffId = $records->keyBy('staff_id');

        $installments = StaffAdvanceInstallment::with('advance')
            ->where('year', $period->year)
            ->where('month', $period->month)
            ->where('status', 'pending')
            ->whereHas('advance', fn ($q) => $q->where('status', 'active')->where('balance', '>', 0))
            ->orderBy('staff_advance_id')
            ->orderBy('sequence')
            ->get();

        // Legacy advances with no schedule rows.
        $legacyAdvances = StaffAdvance::query()
            ->where('status', 'active')
            ->where('balance', '>', 0)
            ->whereDoesntHave('installments')
            ->when($byStaffId->isNotEmpty(), fn ($q) => $q->whereIn('staff_id', $byStaffId->keys()))
            ->get();

        foreach ($legacyAdvances as $advance) {
            if (! $this->legacyDueThisPeriod($advance, $period)) {
                continue;
            }
            $amount = $advance->payrollDeductionAmount();
            if ($amount <= 0) {
                continue;
            }
            $installments->push(new StaffAdvanceInstallment([
                'staff_advance_id' => $advance->id,
                'sequence' => 1,
                'year' => $period->year,
                'month' => $period->month,
                'amount' => $amount,
                'status' => 'pending',
            ]));
            $installments->last()->setRelation('advance', $advance);
            $installments->last()->exists = false;
        }

        $touched = collect();

        foreach ($installments as $installment) {
            $advance = $installment->advance;
            if (! $advance || $advance->status !== 'active') {
                continue;
            }

            $amount = min((float) $installment->amount, (float) $advance->balance);
            if ($amount <= 0) {
                continue;
            }

            $borrowerRecord = $byStaffId->get($advance->staff_id);
            if (! $borrowerRecord) {
                continue;
            }

            $borrowerRecord->advance_deduction = round((float) $borrowerRecord->advance_deduction + $amount, 2);
            $touched->put($borrowerRecord->id ?: 'b'.$borrowerRecord->staff_id, $borrowerRecord);

            $funderRecord = null;
            if ($advance->isFundedByStaff() && $advance->source_staff_id) {
                $funderRecord = $byStaffId->get($advance->source_staff_id);
                if ($funderRecord) {
                    $funderRecord->advance_reimbursement = round(
                        (float) $funderRecord->advance_reimbursement + $amount,
                        2
                    );
                    $touched->put($funderRecord->id ?: 'f'.$funderRecord->staff_id, $funderRecord);
                }
            }

            $advance->recordRepayment($amount);

            $installment->amount = $amount;
            $installment->status = 'collected';
            $installment->borrower_payroll_record_id = $borrowerRecord->id;
            $installment->funder_payroll_record_id = $funderRecord?->id;
            $installment->staff_advance_id = $advance->id;
            $installment->save();
        }

        foreach ($touched as $record) {
            $record->calculateTotals();
            $record->save();
        }
    }

    private function legacyDueThisPeriod(StaffAdvance $advance, PayrollPeriod $period): bool
    {
        if ($advance->repayment_start_year && $advance->repayment_start_month) {
            $start = ((int) $advance->repayment_start_year * 100) + (int) $advance->repayment_start_month;
            $current = ((int) $period->year * 100) + (int) $period->month;

            return $current >= $start;
        }

        return true;
    }
}
