<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PayrollRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'staff_id',
        'salary_structure_id',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'medical_allowance',
        'other_allowances',
        'allowances_breakdown',
        'gross_salary',
        'gross_salary_override',
        'nssf_deduction',
        'employer_nssf_contribution',
        'nhif_deduction',
        'shif_deduction',
        'paye_deduction',
        'housing_levy_deduction',
        'employer_housing_levy_contribution',
        'other_deductions',
        'deductions_breakdown',
        'total_deductions',
        'net_salary',
        'bonus',
        'advance_deduction',
        'custom_deductions_total',
        'custom_deductions_breakdown',
        'adjustments_notes',
        'days_worked',
        'days_in_period',
        'status',
        'paid_at',
        'payslip_number',
        'payslip_generated_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transport_allowance' => 'decimal:2',
        'medical_allowance' => 'decimal:2',
        'other_allowances' => 'decimal:2',
        'allowances_breakdown' => 'array',
        'gross_salary' => 'decimal:2',
        'gross_salary_override' => 'decimal:2',
        'nssf_deduction' => 'decimal:2',
        'employer_nssf_contribution' => 'decimal:2',
        'nhif_deduction' => 'decimal:2',
        'shif_deduction' => 'decimal:2',
        'paye_deduction' => 'decimal:2',
        'housing_levy_deduction' => 'decimal:2',
        'employer_housing_levy_contribution' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'deductions_breakdown' => 'array',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'bonus' => 'decimal:2',
        'advance_deduction' => 'decimal:2',
        'custom_deductions_total' => 'decimal:2',
        'custom_deductions_breakdown' => 'array',
        'days_worked' => 'integer',
        'days_in_period' => 'integer',
        'paid_at' => 'datetime',
        'payslip_generated_at' => 'datetime',
    ];

    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function salaryStructure()
    {
        return $this->belongsTo(SalaryStructure::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate payslip number
     */
    public function generatePayslipNumber()
    {
        if (!$this->payslip_number) {
            $period = $this->payrollPeriod;
            $staffId = str_pad($this->staff_id, 4, '0', STR_PAD_LEFT);
            $this->payslip_number = 'PSL-' . $period->year . str_pad($period->month, 2, '0', STR_PAD_LEFT) . '-' . $staffId . '-' . Str::random(6);
        }
        return $this->payslip_number;
    }

    /**
     * Calculate totals
     */
    public function calculateTotals()
    {
        // Calculate gross salary
        $calculatedGross = $this->basic_salary
            + $this->housing_allowance 
            + $this->transport_allowance 
            + $this->medical_allowance 
            + $this->other_allowances
            + $this->bonus;

        // Add custom allowances
        if ($this->allowances_breakdown && is_array($this->allowances_breakdown)) {
            foreach ($this->allowances_breakdown as $amount) {
                $calculatedGross += (float) $amount;
            }
        }

        $this->gross_salary = $this->gross_salary_override !== null
            ? $this->gross_salary_override
            : $calculatedGross;

        // Calculate total deductions
        $this->total_deductions = $this->nssf_deduction 
            + $this->nhif_deduction 
            + $this->shif_deduction
            + $this->paye_deduction 
            + $this->housing_levy_deduction
            + $this->other_deductions
            + $this->advance_deduction
            + $this->custom_deductions_total;

        // Only fold deductions_breakdown in when custom_deductions_total is unused,
        // otherwise imported kids/uniform/loan amounts get double-counted.
        if ((float) $this->custom_deductions_total <= 0
            && $this->deductions_breakdown
            && is_array($this->deductions_breakdown)) {
            foreach ($this->deductions_breakdown as $amount) {
                $this->total_deductions += (float) $amount;
            }
        }

        // Calculate net salary
        $this->net_salary = $this->gross_salary - $this->total_deductions;

        return $this;
    }

    public function statutoryPaymentsTotal(): float
    {
        return round(
            (float) $this->nssf_deduction
            + (float) $this->nhif_deduction
            + (float) $this->shif_deduction
            + (float) $this->paye_deduction
            + (float) $this->housing_levy_deduction
            + (float) $this->employer_nssf_contribution
            + (float) $this->employer_housing_levy_contribution,
            2,
        );
    }

    public function loanRepaymentsTotal(): float
    {
        return round((float) $this->advance_deduction, 2);
    }

    public function amountRequired(): float
    {
        return round(
            (float) $this->net_salary + $this->statutoryPaymentsTotal() + $this->loanRepaymentsTotal(),
            2,
        );
    }

    /**
     * Prorate monthly earnings for the part of the period covered by employment.
     */
    public function applyEmploymentProration(Staff $staff, PayrollPeriod $period): void
    {
        $periodStart = Carbon::parse($period->start_date)->startOfDay();
        $periodEnd = Carbon::parse($period->end_date)->startOfDay();
        $employmentStart = $staff->hire_date
            ? Carbon::parse($staff->hire_date)->startOfDay()->max($periodStart)
            : $periodStart;
        $employmentEnd = $staff->termination_date
            ? Carbon::parse($staff->termination_date)->startOfDay()->min($periodEnd)
            : $periodEnd;
        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
        $daysWorked = $employmentStart->lte($employmentEnd)
            ? $employmentStart->diffInDays($employmentEnd) + 1
            : 0;
        $factor = $daysInPeriod > 0 ? $daysWorked / $daysInPeriod : 0;

        $this->days_in_period = $daysInPeriod;
        $this->days_worked = $daysWorked;

        foreach ([
            'basic_salary',
            'housing_allowance',
            'transport_allowance',
            'medical_allowance',
            'other_allowances',
            'other_deductions',
        ] as $field) {
            $this->{$field} = round((float) $this->{$field} * $factor, 2);
        }

        if (is_array($this->allowances_breakdown)) {
            $this->allowances_breakdown = collect($this->allowances_breakdown)
                ->map(fn ($amount) => round((float) $amount * $factor, 2))
                ->all();
        }
    }

    /**
     * Check if record can be edited
     */
    public function canEdit()
    {
        return in_array($this->status, ['draft', 'approved']) && !$this->payrollPeriod->isLocked();
    }

    /**
     * Check if record can be rejected/cancelled
     */
    public function canCancel()
    {
        return in_array($this->status, ['draft', 'approved']) && !$this->payrollPeriod->isLocked();
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
