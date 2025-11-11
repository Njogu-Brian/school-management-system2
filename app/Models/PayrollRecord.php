<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'nssf_deduction',
        'nhif_deduction',
        'paye_deduction',
        'other_deductions',
        'deductions_breakdown',
        'total_deductions',
        'net_salary',
        'bonus',
        'advance',
        'loan_deduction',
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
        'nssf_deduction' => 'decimal:2',
        'nhif_deduction' => 'decimal:2',
        'paye_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'deductions_breakdown' => 'array',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'bonus' => 'decimal:2',
        'advance' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
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
        $this->gross_salary = $this->basic_salary 
            + $this->housing_allowance 
            + $this->transport_allowance 
            + $this->medical_allowance 
            + $this->other_allowances
            + $this->bonus;

        // Add custom allowances
        if ($this->allowances_breakdown && is_array($this->allowances_breakdown)) {
            foreach ($this->allowances_breakdown as $amount) {
                $this->gross_salary += (float) $amount;
            }
        }

        // Calculate total deductions
        $this->total_deductions = $this->nssf_deduction 
            + $this->nhif_deduction 
            + $this->paye_deduction 
            + $this->other_deductions
            + $this->advance
            + $this->loan_deduction;

        // Add custom deductions
        if ($this->deductions_breakdown && is_array($this->deductions_breakdown)) {
            foreach ($this->deductions_breakdown as $amount) {
                $this->total_deductions += (float) $amount;
            }
        }

        // Calculate net salary
        $this->net_salary = $this->gross_salary - $this->total_deductions;

        return $this;
    }

    /**
     * Check if record can be edited
     */
    public function canEdit()
    {
        return in_array($this->status, ['draft', 'approved']) && !$this->payrollPeriod->isLocked();
    }
}
