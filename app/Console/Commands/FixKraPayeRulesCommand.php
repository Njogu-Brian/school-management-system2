<?php

namespace App\Console\Commands;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Staff;
use App\Models\StaffStatutoryExemption;
use App\Models\StatutoryRuleset;
use App\Services\PayrollCalculationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixKraPayeRulesCommand extends Command
{
    protected $signature = 'hr:fix-kra-paye-rules
        {--period= : Payroll period id to recalculate (default: latest)}
        {--dry-run : Show changes without writing}';

    protected $description = 'Apply KRA rules: PIN holders are PAYE/housing eligible; housing levy is deductible for PAYE; recalculate period statutory amounts.';

    public function handle(PayrollCalculationService $calc): int
    {
        $dry = (bool) $this->option('dry-run');

        $ruleset = StatutoryRuleset::default()->first() ?? StatutoryRuleset::query()->latest('id')->first();
        if (! $ruleset) {
            $this->error('No statutory ruleset found.');

            return self::FAILURE;
        }

        $params = (array) ($ruleset->params ?? []);
        $taxable = (array) ($params['taxable_income'] ?? []);
        $taxable['subtract_nssf'] = true;
        $taxable['subtract_shif'] = true;
        $taxable['subtract_housing_levy'] = true;
        $params['taxable_income'] = $taxable;
        $params['personal_relief_monthly'] = (float) ($params['personal_relief_monthly'] ?? 2400.0);

        $this->info('Ruleset: '.$ruleset->name);
        $this->line('  taxable_income.subtract_housing_levy = true');

        if (! $dry) {
            $ruleset->params = $params;
            $ruleset->save();
        }

        $pinStaffIds = Staff::query()
            ->whereNotNull('kra_pin')
            ->where('kra_pin', '!=', '')
            ->pluck('id');

        $exemptQuery = StaffStatutoryExemption::query()
            ->whereIn('staff_id', $pinStaffIds)
            ->whereIn('deduction_code', ['paye', 'housing_levy']);

        $toClear = (clone $exemptQuery)->count();
        $this->info("Clearing PAYE/housing exemptions for {$toClear} row(s) on staff with KRA PIN.");

        if (! $dry && $toClear > 0) {
            $exemptQuery->delete();
        }

        $periodId = $this->option('period');
        $period = $periodId
            ? PayrollPeriod::with('statutoryRuleset')->findOrFail($periodId)
            : PayrollPeriod::with('statutoryRuleset')->orderByDesc('id')->first();

        if (! $period) {
            $this->warn('No payroll period to recalculate.');

            return self::SUCCESS;
        }

        $period->loadMissing('statutoryRuleset');
        $activeRuleset = $period->statutoryRuleset ?: $ruleset;

        $records = PayrollRecord::with('staff')
            ->where('payroll_period_id', $period->id)
            ->get();

        $this->info("Recalculating {$records->count()} payroll record(s) for {$period->period_name} (#{$period->id}).");

        $changed = 0;
        foreach ($records as $record) {
            $staff = $record->staff;
            if (! $staff) {
                continue;
            }

            $exemptions = $staff->fresh()->statutoryExemptionCodes();
            // Safety: even if exemption rows remain, PIN holders stay eligible unless dry-run preview only.
            if (filled($staff->kra_pin)) {
                $exemptions = array_values(array_filter(
                    $exemptions,
                    fn ($c) => ! in_array(strtolower((string) $c), ['paye', 'housing_levy'], true)
                ));
            }

            $gross = (float) $record->gross_salary;
            if ($gross <= 0) {
                $record->calculateTotals();
                $gross = (float) $record->gross_salary;
            }

            $deductions = $calc->calculateAllDeductions($gross, $exemptions, $activeRuleset);

            $before = [
                'nssf' => (float) $record->nssf_deduction,
                'shif' => (float) ($record->shif_deduction ?? 0),
                'housing' => (float) ($record->housing_levy_deduction ?? 0),
                'paye' => (float) $record->paye_deduction,
                'net' => (float) $record->net_salary,
            ];

            $record->nssf_deduction = $deductions['nssf'];
            $record->shif_deduction = $deductions['shif'];
            $record->nhif_deduction = $deductions['nhif'];
            $record->housing_levy_deduction = $deductions['housing_levy'];
            $record->paye_deduction = $deductions['paye'];
            $record->calculateTotals();

            $after = [
                'nssf' => (float) $record->nssf_deduction,
                'shif' => (float) $record->shif_deduction,
                'housing' => (float) $record->housing_levy_deduction,
                'paye' => (float) $record->paye_deduction,
                'net' => (float) $record->net_salary,
            ];

            if ($before != $after) {
                $changed++;
                $this->line(sprintf(
                    '  %s | PAYE %s→%s | Housing %s→%s | Net %s→%s',
                    $staff->full_name ?: $staff->staff_id,
                    number_format($before['paye'], 2),
                    number_format($after['paye'], 2),
                    number_format($before['housing'], 2),
                    number_format($after['housing'], 2),
                    number_format($before['net'], 2),
                    number_format($after['net'], 2),
                ));

                if (! $dry) {
                    $record->save();
                }
            }
        }

        if (! $dry) {
            DB::transaction(function () use ($period) {
                $period->calculateTotals();
                $period->save();
            });
        }

        $this->info(($dry ? 'Dry-run: ' : '')."{$changed} record(s) would change / changed.");

        return self::SUCCESS;
    }
}
