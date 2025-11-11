<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_name',
        'year',
        'month',
        'start_date',
        'end_date',
        'pay_date',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'staff_count',
        'processed_at',
        'processed_by',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'pay_date' => 'date',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'staff_count' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function payrollRecords()
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Calculate totals from payroll records
     */
    public function calculateTotals()
    {
        $records = $this->payrollRecords;
        
        $this->total_gross = $records->sum('gross_salary');
        $this->total_deductions = $records->sum('total_deductions');
        $this->total_net = $records->sum('net_salary');
        $this->staff_count = $records->count();
        
        return $this;
    }

    /**
     * Check if period can be processed
     */
    public function canProcess()
    {
        return $this->status === 'draft' || $this->status === 'processing';
    }

    /**
     * Check if period is locked
     */
    public function isLocked()
    {
        return $this->status === 'locked';
    }

    /**
     * Scope: Current period
     */
    public function scopeCurrent($query)
    {
        $now = Carbon::now();
        return $query->where('year', $now->year)
            ->where('month', $now->month);
    }
}
