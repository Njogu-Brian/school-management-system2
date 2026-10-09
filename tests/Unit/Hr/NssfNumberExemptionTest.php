<?php

namespace Tests\Unit\Hr;

use App\Models\Staff;
use App\Models\StatutoryRuleset;
use App\Services\PayrollCalculationService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class NssfNumberExemptionTest extends TestCase
{
    public function test_missing_nssf_number_skips_the_deduction_and_raises_taxable_pay(): void
    {
        $ruleset = new StatutoryRuleset();
        $ruleset->id = 1;
        $ruleset->params = [
            'nssf' => ['tier1_max' => 8000, 'tier2_max' => 72000, 'rate' => 0.06],
            'shif' => ['rate' => 0, 'min' => 0],
            'housing_levy' => ['rate' => 0, 'min' => 0],
            'paye_bands' => [
                ['min' => 0, 'max' => 24000, 'rate' => 0.1],
            ],
            'personal_relief_monthly' => 0,
            'taxable_income' => [
                'subtract_nssf' => true,
                'subtract_shif' => true,
                'subtract_housing_levy' => true,
            ],
        ];

        $calc = new PayrollCalculationService();
        $withNumber = $calc->calculateAllDeductions(20000, [], $ruleset);
        $withoutNumber = $calc->calculateAllDeductions(20000, ['nssf'], $ruleset);

        $this->assertGreaterThan(0, $withNumber['nssf']);
        $this->assertSame(0.0, $withoutNumber['nssf']);
        $this->assertGreaterThan($withNumber['paye'], $withoutNumber['paye']);
    }

    public function test_staff_without_an_nssf_number_is_exempt_from_payroll_nssf(): void
    {
        $blank = $this->staffWithNssf('   ');
        $missing = $this->staffWithNssf(null);
        $numbered = $this->staffWithNssf('2027410852');

        $this->assertContains('nssf', $blank->payrollExemptionCodes());
        $this->assertContains('nssf', $missing->payrollExemptionCodes());
        $this->assertNotContains('nssf', $numbered->payrollExemptionCodes());
    }

    private function staffWithNssf(?string $nssf): Staff
    {
        $staff = new Staff();
        $staff->nssf = $nssf;
        $staff->setRelation('statutoryExemptions', new Collection());

        return $staff;
    }
}
