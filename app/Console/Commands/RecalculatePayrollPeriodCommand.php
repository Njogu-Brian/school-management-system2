<?php

namespace App\Console\Commands;

use App\Models\CustomDeduction;
use App\Models\Expense;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\SalaryHistory;
use App\Models\Staff;
use App\Models\StaffAdvance;
use App\Services\PayrollCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculatePayrollPeriodCommand extends Command
{
    protected $signature = 'hr:recalculate-payroll-period
        {period? : Payroll period id (default: latest)}
        {--staff= : Optional comma-separated staff ids to recalculate}
        {--dry-run : Preview changes without writing}';

    protected $description = 'Recalculate a payroll period: refresh earnings, statutory, advances, and custom deductions from current setup.';

    public function handle(PayrollCalculationService $calc): int
    {
        $dry = (bool) $this->option('dry-run');
        $periodId = $this->argument('period');

        $period = $periodId
            ? PayrollPeriod::with('statutoryRuleset')->findOrFail($periodId)
            : PayrollPeriod::with('statutoryRuleset')->orderByDesc('id')->first();

        if (! $period) {
            $this->error('No payroll period found.');

            return self::FAILURE;
        }

        if ($period->status === 'locked') {
            $this->error("Period #{$period->id} is locked. Unlock it first if you must recalculate.");

            return self::FAILURE;
        }

        $staffFilter = collect(explode(',', (string) $this->option('staff')))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->values()
            ->all();

        $this->info("Period: {$period->period_name} (#{$period->id}) status={$period->status}");
        $this->line('Refreshing earnings, statutory, advances, and custom deductions from current setup.');
        $this->line('Preserving manual bonus and cancelled slips. Paid slips are left unchanged.');
        if ($dry) {
            $this->warn('Dry-run only — no changes written.');
        }

        $updated = 0;
        $created = 0;
        $ruleset = $period->statutoryRuleset;

        try {
            DB::beginTransaction();

            $records = PayrollRecord::with(['staff.statutoryExemptions', 'staff.activeSalaryStructure'])
                ->where('payroll_period_id', $period->id)
                ->whereIn('status', ['draft', 'approved'])
                ->when($staffFilter, fn ($q) => $q->whereIn('staff_id', $staffFilter))
                ->get();

            foreach ($records as $record) {
                $staff = $record->staff;
                if (! $staff) {
                    continue;
                }

                $beforeNet = (float) $record->net_salary;
                $this->reverseAppliedDeductions($record);
                $this->rebuildRecord($record, $staff, $period, $calc, $ruleset);
                $record->save();
                $this->syncSalaryHistory($record, $period);

                $updated++;
                $this->line(sprintf(
                    '  Updated %s | Net %s → %s',
                    $staff->name ?: $staff->staff_id,
                    number_format($beforeNet, 2),
                    number_format((float) $record->net_salary, 2),
                ));
            }

            // Also create slips for active staff missing a live record (e.g. after cancel, or new hires).
            if ($staffFilter === []) {
                $existingStaffIds = PayrollRecord::where('payroll_period_id', $period->id)
                    ->whereIn('status', ['draft', 'approved', 'paid'])
                    ->pluck('staff_id')
                    ->all();

                $staffQuery = Staff::with(['statutoryExemptions', 'activeSalaryStructure'])
                    ->where('status', 'active');

                foreach ($staffQuery->get() as $member) {
                    if (in_array($member->id, $existingStaffIds, true)) {
                        continue;
                    }

                    $salaryStructure = $member->activeSalaryStructure;
                    if (! $salaryStructure && ! $member->basic_salary) {
                        continue;
                    }

                    $record = new PayrollRecord();
                    $record->payroll_period_id = $period->id;
                    $record->staff_id = $member->id;
                    $record->created_by = auth()->id();
                    $record->bonus = 0;
                    $record->status = 'approved';

                    $this->rebuildRecord($record, $member, $period, $calc, $ruleset);
                    $record->save();

                    SalaryHistory::create([
                        'staff_id' => $member->id,
                        'payroll_record_id' => $record->id,
                        'basic_salary' => $record->basic_salary,
                        'gross_salary' => $record->gross_salary,
                        'total_deductions' => $record->total_deductions,
                        'net_salary' => $record->net_salary,
                        'year' => $period->year,
                        'month' => $period->month,
                        'pay_date' => $period->pay_date,
                        'change_type' => 'payroll',
                        'created_by' => auth()->id(),
                    ]);

                    $created++;
                    $this->line(sprintf(
                        '  Created %s | Net %s',
                        $member->name ?: $member->staff_id,
                        number_format((float) $record->net_salary, 2),
                    ));
                }
            }

            $period->refresh();
            $period->load('payrollRecords');
            $period->calculateTotals();
            $period->processed_at = now();
            if (auth()->id()) {
                $period->processed_by = auth()->id();
            }
            $period->save();
            $this->syncExpenseTotals($period);

            if ($dry) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error('Recalculation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(($dry ? 'Dry-run: ' : '')."{$updated} updated, {$created} created.");

        if (! $dry && ($updated > 0 || $created > 0)) {
            $this->comment('Re-download exports from the period Exports menu if needed.');
        }

        return self::SUCCESS;
    }

    private function rebuildRecord(
        PayrollRecord $record,
        Staff $member,
        PayrollPeriod $period,
        PayrollCalculationService $calc,
        $ruleset,
    ): void {
        $salaryStructure = $member->activeSalaryStructure;
        $record->salary_structure_id = $salaryStructure->id ?? null;

        if ($salaryStructure) {
            $record->basic_salary = $salaryStructure->basic_salary;
            $record->housing_allowance = $salaryStructure->housing_allowance;
            $record->transport_allowance = $salaryStructure->transport_allowance;
            $record->medical_allowance = $salaryStructure->medical_allowance;
            $record->other_allowances = $salaryStructure->other_allowances;
            $record->allowances_breakdown = $salaryStructure->allowances_breakdown;
            $record->other_deductions = $salaryStructure->other_deductions ?? 0;
            $record->deductions_breakdown = $salaryStructure->deductions_breakdown ?? null;
        } else {
            $record->basic_salary = $member->basic_salary ?? 0;
            $record->housing_allowance = 0;
            $record->transport_allowance = 0;
            $record->medical_allowance = 0;
            $record->other_allowances = 0;
            $record->allowances_breakdown = null;
            $record->other_deductions = 0;
            $record->deductions_breakdown = null;
        }

        // Bonus is a manual slip adjustment — keep it.
        $record->bonus = (float) ($record->bonus ?? 0);

        $record->advance_deduction = 0;
        $record->custom_deductions_total = 0;
        $record->custom_deductions_breakdown = [];
        $record->calculateTotals();

        $deductions = $calc->calculateAllDeductions(
            (float) $record->gross_salary,
            $member->statutoryExemptionCodes(),
            $ruleset,
        );
        $record->nssf_deduction = $deductions['nssf'];
        $record->nhif_deduction = $deductions['nhif'];
        $record->shif_deduction = $deductions['shif'];
        $record->paye_deduction = $deductions['paye'];
        $record->housing_levy_deduction = $deductions['housing_levy'];

        $advanceDeduction = 0;
        foreach ($member->activeAdvances()->get() as $advance) {
            $deductionAmount = $advance->payrollDeductionAmount();
            if ($deductionAmount > 0) {
                $advanceDeduction += $deductionAmount;
                $advance->recordRepayment($deductionAmount);
            }
        }
        $record->advance_deduction = $advanceDeduction;

        $customDeductionsTotal = 0;
        $customDeductionsBreakdown = [];
        $activeCustomDeductions = $member->activeCustomDeductions()
            ->where('effective_from', '<=', $period->end_date)
            ->where(function ($q) use ($period) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $period->start_date);
            })
            ->get();

        foreach ($activeCustomDeductions as $customDeduction) {
            if (! $customDeduction->shouldDeductThisMonth($period->year, $period->month)) {
                continue;
            }

            $deductionAmount = (float) $customDeduction->amount;

            if ($customDeduction->total_amount && $customDeduction->amount_deducted >= $customDeduction->total_amount) {
                continue;
            }

            if ($customDeduction->total_amount) {
                $remaining = (float) $customDeduction->total_amount - (float) $customDeduction->amount_deducted;
                $deductionAmount = min($deductionAmount, $remaining);
            }

            if ($deductionAmount <= 0) {
                continue;
            }

            $customDeductionsTotal += $deductionAmount;
            $customDeductionsBreakdown[$customDeduction->deduction_type_id] =
                ($customDeductionsBreakdown[$customDeduction->deduction_type_id] ?? 0) + $deductionAmount;

            $customDeduction->recordDeduction($deductionAmount);
        }

        $record->custom_deductions_total = $customDeductionsTotal;
        $record->custom_deductions_breakdown = $customDeductionsBreakdown;
        $record->calculateTotals();

        if ($record->status === 'draft') {
            $record->status = 'approved';
        }
    }

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

        $record->advance_deduction = 0;
        $record->custom_deductions_total = 0;
        $record->custom_deductions_breakdown = [];
    }

    private function syncSalaryHistory(PayrollRecord $record, PayrollPeriod $period): void
    {
        $history = SalaryHistory::where('payroll_record_id', $record->id)->first();
        if ($history) {
            $history->update([
                'basic_salary' => $record->basic_salary,
                'gross_salary' => $record->gross_salary,
                'total_deductions' => $record->total_deductions,
                'net_salary' => $record->net_salary,
            ]);

            return;
        }

        SalaryHistory::create([
            'staff_id' => $record->staff_id,
            'payroll_record_id' => $record->id,
            'basic_salary' => $record->basic_salary,
            'gross_salary' => $record->gross_salary,
            'total_deductions' => $record->total_deductions,
            'net_salary' => $record->net_salary,
            'year' => $period->year,
            'month' => $period->month,
            'pay_date' => $period->pay_date,
            'change_type' => 'payroll',
            'created_by' => auth()->id(),
        ]);
    }

    private function syncExpenseTotals(PayrollPeriod $period): void
    {
        if (! $period->expense_id) {
            return;
        }

        $expense = Expense::with('lines')->find($period->expense_id);
        if (! $expense) {
            return;
        }

        $records = $period->payrollRecords->where('status', '!=', 'cancelled');
        $totalGross = round((float) $records->sum('gross_salary'), 2);
        $totalNet = round((float) $records->sum('net_salary'), 2);
        $totalDeductions = round((float) $records->sum('total_deductions'), 2);
        $amount = $totalGross > 0 ? $totalGross : $totalNet;
        $staffCount = $records->count();

        $expense->notes = sprintf(
            'Payroll — %s | Staff: %d | Gross: %s | Deductions: %s | Net: %s (recalculated)',
            $period->period_name ?? $period->name,
            $staffCount,
            number_format($totalGross, 2, '.', ''),
            number_format($totalDeductions, 2, '.', ''),
            number_format($totalNet, 2, '.', ''),
        );
        $expense->save();

        $line = $expense->lines->first();
        if ($line) {
            $line->unit_cost = $amount;
            $line->line_total = $amount;
            $line->description = sprintf(
                'Salaries & wages — %s (%d staff)',
                $period->period_name ?? $period->name,
                $staffCount,
            );
            $line->save();
        }

        if (method_exists($expense, 'recalculateTotals')) {
            $expense->recalculateTotals();
            $expense->save();
        }
    }
}
