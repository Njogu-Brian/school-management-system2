<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StaffAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'amount',
        'requested_amount',
        'purpose',
        'description',
        'advance_date',
        'repayment_method',
        'installment_count',
        'monthly_deduction_amount',
        'amount_repaid',
        'balance',
        'status',
        'expected_completion_date',
        'completed_date',
        'approved_by',
        'approved_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_amount' => 'decimal:2',
        'monthly_deduction_amount' => 'decimal:2',
        'amount_repaid' => 'decimal:2',
        'balance' => 'decimal:2',
        'advance_date' => 'date',
        'expected_completion_date' => 'date',
        'completed_date' => 'date',
        'approved_at' => 'datetime',
        'installment_count' => 'integer',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customDeductions()
    {
        return $this->hasMany(CustomDeduction::class);
    }

    /**
     * Calculate balance
     */
    public function calculateBalance()
    {
        $this->balance = $this->amount - $this->amount_repaid;
        
        if ($this->balance <= 0) {
            $this->status = 'completed';
            $this->completed_date = Carbon::now();
        }
        
        $this->save();
        return $this->balance;
    }

    /**
     * Record repayment
     */
    public function recordRepayment($amount)
    {
        $this->amount_repaid += $amount;
        $this->calculateBalance();
    }

    /**
     * Reverse a previously recorded payroll repayment (e.g. cancelled payslip).
     */
    public function reverseRepayment($amount)
    {
        $amount = min((float) $amount, (float) $this->amount_repaid);
        if ($amount <= 0) {
            return;
        }

        $this->amount_repaid = max(0, (float) $this->amount_repaid - $amount);
        $this->balance = (float) $this->amount - (float) $this->amount_repaid;

        if ($this->status === 'completed' && $this->balance > 0) {
            $this->status = 'active';
            $this->completed_date = null;
        }

        $this->save();
    }

    /**
     * Check if advance is active
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->balance > 0;
    }
}
