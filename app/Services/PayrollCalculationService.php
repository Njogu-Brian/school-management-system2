<?php

namespace App\Services;

/**
 * Service for calculating payroll deductions (PAYE, NSSF, NHIF)
 * Based on Kenyan tax rates as of 2024
 */
class PayrollCalculationService
{
    // NSSF Tier 1: 0 - 6,000 (Employee: 6%, Employer: 6%)
    // NSSF Tier 2: 6,001 - 18,000 (Employee: 6%, Employer: 6%)
    private const NSSF_TIER_1_MAX = 6000;
    private const NSSF_TIER_2_MAX = 18000;
    private const NSSF_RATE = 0.06; // 6%

    // NHIF rates (monthly)
    private const NHIF_RATES = [
        0 => 150,
        6000 => 300,
        8000 => 400,
        12000 => 500,
        15000 => 600,
        20000 => 750,
        25000 => 850,
        30000 => 900,
        35000 => 950,
        40000 => 1000,
        50000 => 1100,
        60000 => 1200,
        70000 => 1300,
        80000 => 1400,
        90000 => 1500,
        100000 => 1600,
    ];

    // PAYE rates (2024 Kenya)
    private const PAYE_RATES = [
        ['min' => 0, 'max' => 288000, 'rate' => 0.10], // 10%
        ['min' => 288001, 'max' => 388000, 'rate' => 0.25], // 25%
        ['min' => 388001, 'max' => PHP_INT_MAX, 'rate' => 0.30], // 30%
    ];

    private const PERSONAL_RELIEF = 2880; // Monthly personal relief

    /**
     * Calculate NSSF deduction
     */
    public function calculateNSSF(float $grossSalary): float
    {
        $nssf = 0;

        // Tier 1
        if ($grossSalary > 0) {
            $tier1Amount = min($grossSalary, self::NSSF_TIER_1_MAX);
            $nssf += $tier1Amount * self::NSSF_RATE;
        }

        // Tier 2
        if ($grossSalary > self::NSSF_TIER_1_MAX) {
            $tier2Amount = min($grossSalary - self::NSSF_TIER_1_MAX, self::NSSF_TIER_2_MAX - self::NSSF_TIER_1_MAX);
            $nssf += $tier2Amount * self::NSSF_RATE;
        }

        return round($nssf, 2);
    }

    /**
     * Calculate NHIF deduction
     */
    public function calculateNHIF(float $grossSalary): float
    {
        $nhif = 0;

        // Find the appropriate rate
        krsort(self::NHIF_RATES);
        foreach (self::NHIF_RATES as $threshold => $rate) {
            if ($grossSalary >= $threshold) {
                $nhif = $rate;
                break;
            }
        }

        return $nhif;
    }

    /**
     * Calculate PAYE (Pay As You Earn)
     */
    public function calculatePAYE(float $grossSalary, float $nssfDeduction = 0, float $nhifDeduction = 0): float
    {
        // Taxable income = Gross - NSSF - NHIF
        $taxableIncome = $grossSalary - $nssfDeduction - $nhifDeduction;

        if ($taxableIncome <= 0) {
            return 0;
        }

        $paye = 0;
        $remainingIncome = $taxableIncome;

        foreach (self::PAYE_RATES as $rate) {
            if ($remainingIncome <= 0) {
                break;
            }

            $bandMin = $rate['min'];
            $bandMax = $rate['max'];
            $ratePercent = $rate['rate'];

            if ($taxableIncome > $bandMin) {
                $bandAmount = min($remainingIncome, $bandMax - $bandMin);
                $paye += $bandAmount * $ratePercent;
                $remainingIncome -= $bandAmount;
            }
        }

        // Apply personal relief
        $paye = max(0, $paye - self::PERSONAL_RELIEF);

        return round($paye, 2);
    }

    /**
     * Calculate all deductions for a given gross salary
     */
    public function calculateAllDeductions(float $grossSalary): array
    {
        $nssf = $this->calculateNSSF($grossSalary);
        $nhif = $this->calculateNHIF($grossSalary);
        $paye = $this->calculatePAYE($grossSalary, $nssf, $nhif);

        return [
            'nssf' => $nssf,
            'nhif' => $nhif,
            'paye' => $paye,
            'total' => $nssf + $nhif + $paye,
        ];
    }

    /**
     * Calculate net salary
     */
    public function calculateNetSalary(float $grossSalary, float $totalDeductions): float
    {
        return round($grossSalary - $totalDeductions, 2);
    }
}

