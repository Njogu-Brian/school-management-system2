<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Services\PayrollCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculatePayrollPeriodCommand extends Command
{
    protected $signature = 'hr:recalculate-payroll-period
        {period? : Payroll period id (default: latest)}
        {--staff= : Optional comma-separated staff ids to recalculate}
        {--dry-run : Preview changes without writing}';

    protected $description = 'Recalculate statutory deductions on an existing payroll period using current staff exemptions (keeps kids/uniform/loan/advance).';

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

        $records = PayrollRecord::with('staff.statutoryExemptions')
            ->where('payroll_period_id', $period->id)
            ->when($staffFilter, fn ($q) => $q->whereIn('staff_id', $staffFilter))
            ->get();

        $this->info("Period: {$period->period_name} (#{$period->id}) status={$period->status}");
        $this->line('Recalculating statutory NSSF/SHIF/housing/PAYE from current exemptions.');
        $this->line('Preserving kids fees / uniform / loan / advance / other custom amounts.');
        if ($dry) {
            $this->warn('Dry-run only — no changes written.');
        }

        $changed = 0;
        $ruleset = $period->statutoryRuleset;

        foreach ($records as $record) {
            $staff = $record->staff;
            if (! $staff) {
                continue;
            }

            $gross = (float) $record->gross_salary;
            if ($gross <= 0) {
                $record->calculateTotals();
                $gross = (float) $record->gross_salary;
            }

            $exemptions = $staff->statutoryExemptionCodes();
            $deductions = $calc->calculateAllDeductions($gross, $exemptions, $ruleset);

            $before = [
                'nssf' => (float) $record->nssf_deduction,
                'shif' => (float) ($record->shif_deduction ?? 0),
                'housing' => (float) ($record->housing_levy_deduction ?? 0),
                'paye' => (float) $record->paye_deduction,
                'net' => (float) $record->net_salary,
                'total' => (float) $record->total_deductions,
            ];

            $nssfDelta = round($deductions['nssf'] - $before['nssf'], 2);
            $shifDelta = round($deductions['shif'] - $before['shif'], 2);
            $housingDelta = round($deductions['housing_levy'] - $before['housing'], 2);
            $payeDelta = round($deductions['paye'] - $before['paye'], 2);
            $statDelta = round($nssfDelta + $shifDelta + $housingDelta + $payeDelta, 2);

            if (abs($statDelta) < 0.01) {
                continue;
            }

            $record->nssf_deduction = $deductions['nssf'];
            $record->shif_deduction = $deductions['shif'];
            $record->nhif_deduction = $deductions['nhif'];
            $record->housing_levy_deduction = $deductions['housing_levy'];
            $record->paye_deduction = $deductions['paye'];
            $record->total_deductions = round($before['total'] + $statDelta, 2);
            $record->net_salary = round($gross - (float) $record->total_deductions, 2);

            $changed++;
            $this->line(sprintf(
                '  %s | NSSF %s→%s | SHIF %s→%s | Housing %s→%s | PAYE %s→%s | Net %s→%s',
                $staff->full_name ?: $staff->staff_id,
                number_format($before['nssf'], 2),
                number_format((float) $record->nssf_deduction, 2),
                number_format($before['shif'], 2),
                number_format((float) $record->shif_deduction, 2),
                number_format($before['housing'], 2),
                number_format((float) $record->housing_levy_deduction, 2),
                number_format($before['paye'], 2),
                number_format((float) $record->paye_deduction, 2),
                number_format($before['net'], 2),
                number_format((float) $record->net_salary, 2),
            ));

            if (! $dry) {
                $record->save();
            }
        }

        if (! $dry && $changed > 0) {
            DB::transaction(function () use ($period) {
                $period->refresh();
                $period->load('records');
                $period->calculateTotals();
                $period->save();
                $this->syncExpenseTotals($period);
            });
        }

        $this->info(($dry ? 'Dry-run: ' : '')."{$changed} record(s) changed.");

        if (! $dry && $changed > 0) {
            $this->comment('Re-download NSSF / SHIF / KRA exports from the period Exports menu.');
        }

        return self::SUCCESS;
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

        $totalGross = round((float) $period->records->sum('gross_salary'), 2);
        $totalNet = round((float) $period->records->sum('net_salary'), 2);
        $totalDeductions = round((float) $period->records->sum('total_deductions'), 2);
        $amount = $totalGross > 0 ? $totalGross : $totalNet;

        $expense->notes = sprintf(
            'Payroll — %s | Staff: %d | Gross: %s | Deductions: %s | Net: %s (recalculated)',
            $period->period_name ?? $period->name,
            $period->records->count(),
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
                $period->records->count(),
            );
            $line->save();
        }

        if (method_exists($expense, 'recalculateTotals')) {
            $expense->recalculateTotals();
            $expense->save();
        }
    }
}
