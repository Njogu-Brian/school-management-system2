<?php

namespace App\Console\Commands;

use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Staff;
use App\Models\StaffStatutoryExemption;
use App\Models\StatutoryRuleset;
use Illuminate\Console\Command;

class FixKraPayeRulesCommand extends Command
{
    protected $signature = 'hr:fix-kra-paye-rules
        {--period= : Payroll period id to recalculate (default: latest)}
        {--dry-run : Show changes without writing}';

    protected $description = 'Apply KRA rules: PIN holders are PAYE/housing eligible; housing levy deductible for PAYE; recalculate PAYE/housing while preserving imported NSSF/SHIF.';

    public function handle(): int
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

        // Current NSSF upper earnings limit (employee 6%) — used for future payroll runs.
        $nssf = (array) ($params['nssf'] ?? []);
        $nssf['tier1_max'] = (float) ($nssf['tier1_max'] ?? 8000.0);
        $nssf['tier2_max'] = max((float) ($nssf['tier2_max'] ?? 0), 72000.0);
        $nssf['rate'] = (float) ($nssf['rate'] ?? 0.06);
        $params['nssf'] = $nssf;

        $this->info('Ruleset: '.$ruleset->name);
        $this->line('  subtract_housing_levy=true, nssf.tier2_max='.$nssf['tier2_max']);

        if (! $dry) {
            $ruleset->params = $params;
            $ruleset->save();
        }

        // Temporary in-memory ruleset for this run (even on dry-run).
        $ruleset->params = $params;

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
            ? PayrollPeriod::findOrFail($periodId)
            : PayrollPeriod::query()->orderByDesc('id')->first();

        if (! $period) {
            $this->warn('No payroll period to recalculate.');

            return self::SUCCESS;
        }

        $records = PayrollRecord::with('staff.statutoryExemptions')
            ->where('payroll_period_id', $period->id)
            ->get();

        $this->info("Recalculating PAYE/housing for {$records->count()} record(s) in {$period->period_name} (#{$period->id}).");
        $this->line('Preserving existing NSSF/SHIF and post-tax deductions from the imported payroll.');

        $housingRate = (float) (($params['housing_levy']['rate'] ?? 0.015));
        $changed = 0;

        foreach ($records as $record) {
            $staff = $record->staff;
            if (! $staff) {
                continue;
            }

            $hasPin = filled(trim((string) $staff->kra_pin));
            $exemptions = collect($staff->statutoryExemptionCodes())
                ->map(fn ($c) => strtolower((string) $c));

            if ($hasPin) {
                $exemptions = $exemptions->reject(fn ($c) => in_array($c, ['paye', 'housing_levy'], true));
            }

            $gross = (float) $record->gross_salary;
            if ($gross <= 0) {
                $record->calculateTotals();
                $gross = (float) $record->gross_salary;
            }

            $nssfAmt = (float) $record->nssf_deduction;
            $shifAmt = (float) ($record->shif_deduction ?? 0);

            $housingExempt = $exemptions->contains('housing_levy');
            $payeExempt = $exemptions->contains('paye');

            $housingAmt = $housingExempt
                ? 0.0
                : round(max(0.0, $gross) * $housingRate, 2);

            $payeAmt = 0.0;
            if (! $payeExempt) {
                $taxableIncome = max(0.0, $gross - $nssfAmt - $shifAmt - $housingAmt);
                $payeAmt = round($this->calculatePaye($taxableIncome, $params), 2);
            }

            $before = [
                'housing' => (float) ($record->housing_levy_deduction ?? 0),
                'paye' => (float) $record->paye_deduction,
                'net' => (float) $record->net_salary,
            ];

            $record->housing_levy_deduction = $housingAmt;
            $record->paye_deduction = $payeAmt;
            $record->calculateTotals();

            $after = [
                'housing' => (float) $record->housing_levy_deduction,
                'paye' => (float) $record->paye_deduction,
                'net' => (float) $record->net_salary,
            ];

            if (abs($before['housing'] - $after['housing']) > 0.009
                || abs($before['paye'] - $after['paye']) > 0.009
                || abs($before['net'] - $after['net']) > 0.009) {
                $changed++;
                $this->line(sprintf(
                    '  %s%s | PAYE %s→%s | Housing %s→%s | Net %s→%s',
                    $staff->full_name ?: $staff->staff_id,
                    $hasPin ? ' [PIN]' : '',
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
            $period->calculateTotals();
            $period->save();
        }

        $this->info(($dry ? 'Dry-run: ' : '')."{$changed} record(s) changed.");

        return self::SUCCESS;
    }

    private function calculatePaye(float $taxableIncome, array $params): float
    {
        $bands = (array) ($params['paye_bands'] ?? []);
        $personalRelief = (float) ($params['personal_relief_monthly'] ?? 2400.0);
        if ($taxableIncome <= 0) {
            return 0.0;
        }

        $paye = 0.0;
        foreach ($bands as $band) {
            $min = (float) ($band['min'] ?? 0.0);
            $max = $band['max'] ?? null;
            $rate = (float) ($band['rate'] ?? 0.0);

            if ($taxableIncome <= $min) {
                continue;
            }
            $upper = $max === null ? $taxableIncome : min($taxableIncome, (float) $max);
            $bandAmount = max(0.0, $upper - $min);
            if ($bandAmount <= 0) {
                continue;
            }
            $paye += $bandAmount * $rate;
        }

        return max(0.0, $paye - $personalRelief);
    }
}
