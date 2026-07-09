<?php

namespace App\Services;

use App\Models\StatutoryRuleset;

/**
 * Versioned service for calculating payroll deductions (PAYE, NSSF, SHIF, Housing Levy, legacy NHIF).
 *
 * IMPORTANT:
 * - Numbers change with Finance Acts. We compute from a per-period ruleset (`StatutoryRuleset`).
 * - Staff-specific exemptions are applied by deduction_code (nssf, shif, housing_levy, paye, nhif).
 */
class PayrollCalculationService
{
    /**
     * Calculate all statutory deductions for a period, given gross salary.
     *
     * @param  array<int,string>  $exemptions  e.g. ['nssf','shif','paye']
     */
    public function calculateAllDeductions(
        float $grossSalary,
        array $exemptions = [],
        ?StatutoryRuleset $ruleset = null,
    ): array {
        $ruleset ??= StatutoryRuleset::default()->first();
        if (! $ruleset) {
            throw new \RuntimeException('No statutory ruleset configured.');
        }

        $params = (array) ($ruleset->params ?? []);
        $exemptions = collect($exemptions)->map(fn ($c) => strtolower((string) $c))->unique();

        $nssf = $exemptions->contains('nssf') ? 0.0 : $this->calculateNssfFromRuleset($grossSalary, $params);
        $shif = $exemptions->contains('shif') ? 0.0 : $this->calculateShifFromRuleset($grossSalary, $params);
        $housing = $exemptions->contains('housing_levy') ? 0.0 : $this->calculateHousingFromRuleset($grossSalary, $params);

        $legacyNhif = $exemptions->contains('nhif') ? 0.0 : 0.0;

        $taxableIncome = $grossSalary;
        $taxableCfg = (array) ($params['taxable_income'] ?? []);
        if (($taxableCfg['subtract_nssf'] ?? true) === true) {
            $taxableIncome -= $nssf;
        }
        if (($taxableCfg['subtract_shif'] ?? true) === true) {
            $taxableIncome -= $shif;
        }
        if (($taxableCfg['subtract_nhif'] ?? false) === true) {
            $taxableIncome -= $legacyNhif;
        }
        $taxableIncome = max(0.0, $taxableIncome);

        $paye = $exemptions->contains('paye') ? 0.0 : $this->calculatePayeFromRuleset($taxableIncome, $params);

        return [
            'ruleset_id' => $ruleset->id,
            'nssf' => round($nssf, 2),
            'shif' => round($shif, 2),
            'housing_levy' => round($housing, 2),
            'nhif' => round($legacyNhif, 2),
            'paye' => round($paye, 2),
            'taxable_income' => round($taxableIncome, 2),
            'total' => round($nssf + $shif + $housing + $legacyNhif + $paye, 2),
        ];
    }

    private function calculateNssfFromRuleset(float $grossSalary, array $params): float
    {
        $nssf = (array) ($params['nssf'] ?? []);
        $tier1Max = (float) ($nssf['tier1_max'] ?? 6000.0);
        $tier2Max = (float) ($nssf['tier2_max'] ?? 18000.0);
        $rate = (float) ($nssf['rate'] ?? 0.06);

        $grossSalary = max(0.0, $grossSalary);
        $tier1 = min($grossSalary, $tier1Max);
        $tier2 = 0.0;
        if ($grossSalary > $tier1Max) {
            $tier2 = min($grossSalary - $tier1Max, max(0.0, $tier2Max - $tier1Max));
        }
        return ($tier1 + $tier2) * $rate;
    }

    private function calculateShifFromRuleset(float $grossSalary, array $params): float
    {
        $shif = (array) ($params['shif'] ?? []);
        $rate = (float) ($shif['rate'] ?? 0.0);
        $min = (float) ($shif['min'] ?? 0.0);
        $amount = max(0.0, $grossSalary) * $rate;
        return max($min, $amount);
    }

    private function calculateHousingFromRuleset(float $grossSalary, array $params): float
    {
        $h = (array) ($params['housing_levy'] ?? []);
        $rate = (float) ($h['rate'] ?? 0.0);
        $min = (float) ($h['min'] ?? 0.0);
        $amount = max(0.0, $grossSalary) * $rate;
        return max($min, $amount);
    }

    private function calculatePayeFromRuleset(float $taxableIncome, array $params): float
    {
        $bands = (array) ($params['paye_bands'] ?? []);
        $personalRelief = (float) ($params['personal_relief_monthly'] ?? 0.0);
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

        $paye = max(0.0, $paye - $personalRelief);
        return $paye;
    }
}

