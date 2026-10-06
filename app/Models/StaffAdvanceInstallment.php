<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAdvanceInstallment extends Model
{
    protected $fillable = [
        'staff_advance_id',
        'sequence',
        'year',
        'month',
        'amount',
        'status',
        'borrower_payroll_record_id',
        'funder_payroll_record_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sequence' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function advance(): BelongsTo
    {
        return $this->belongsTo(StaffAdvance::class, 'staff_advance_id');
    }

    public function borrowerPayrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class, 'borrower_payroll_record_id');
    }

    public function funderPayrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class, 'funder_payroll_record_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function periodLabel(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
