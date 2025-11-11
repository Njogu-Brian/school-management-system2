<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'medical_allowance',
        'other_allowances',
        'allowances_breakdown',
        'nssf_deduction',
        'nhif_deduction',
        'paye_deduction',
        'other_deductions',
        'deductions_breakdown',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'effective_from',
        'effective_to',
        'is_active',
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
        'nssf_deduction' => 'decimal:2',
        'nhif_deduction' => 'decimal:2',
        'paye_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'deductions_breakdown' => 'array',
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    /**
     * Calculate totals based on components
     */
    public function calculateTotals()
    {
        // Calculate gross salary
        $this->gross_salary = $this->basic_salary 
            + $this->housing_allowance 
            + $this->transport_allowance 
            + $this->medical_allowance 
            + $this->other_allowances;

        // Add custom allowances from breakdown
        if ($this->allowances_breakdown && is_array($this->allowances_breakdown)) {
            foreach ($this->allowances_breakdown as $amount) {
                $this->gross_salary += (float) $amount;
            }
        }

        // Calculate total deductions
        $this->total_deductions = $this->nssf_deduction 
            + $this->nhif_deduction 
            + $this->paye_deduction 
            + $this->other_deductions;

        // Add custom deductions from breakdown
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
     * Check if structure is currently active
     */
    public function isCurrentlyActive()
    {
        $now = Carbon::now();
        return $this->is_active 
            && $this->effective_from <= $now 
            && ($this->effective_to === null || $this->effective_to >= $now);
    }

    /**
     * Scope: Active structures
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where('effective_from', '<=', $now)
            ->where(function($q) use ($now) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $now);
            });
    }
}
