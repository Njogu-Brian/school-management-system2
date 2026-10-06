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
        'source_type',
        'source_staff_id',
        'repayment_start_year',
        'repayment_start_month',
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
        'repayment_start_year' => 'integer',
        'repayment_start_month' => 'integer',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function sourceStaff()
    {
        return $this->belongsTo(Staff::class, 'source_staff_id');
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

    public function installments()
    {
        return $this->hasMany(StaffAdvanceInstallment::class)->orderBy('sequence');
    }

    public function isFundedByStaff(): bool
    {
        return ($this->source_type ?? 'company') === 'staff' && ! empty($this->source_staff_id);
    }

    public function sourceLabel(): string
    {
        if ($this->isFundedByStaff()) {
            return $this->sourceStaff?->name ?? ('Staff #'.$this->source_staff_id);
        }

        return 'Company (Royal Kings)';
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

    /**
     * Amount payroll should recover for this advance in one run (legacy / no schedule).
     */
    public function payrollDeductionAmount(): float
    {
        if ($this->status !== 'active') {
            return 0.0;
        }

        $balance = (float) $this->balance;
        if ($balance <= 0) {
            return 0.0;
        }

        $installment = match ($this->repayment_method) {
            'monthly_deduction' => (float) ($this->monthly_deduction_amount ?? 0),
            'installments' => $this->installment_count > 0
                ? round((float) $this->amount / (int) $this->installment_count, 2)
                : $balance,
            default => $balance,
        };

        if ($installment <= 0) {
            return 0.0;
        }

        return min($installment, $balance);
    }

    /**
     * Build / rebuild pending installment rows from start month + repayment terms.
     * Collected/skipped rows are left untouched; only pending rows are replaced.
     */
    public function generateInstallmentSchedule(bool $replacePending = true): void
    {
        $year = (int) ($this->repayment_start_year ?? 0);
        $month = (int) ($this->repayment_start_month ?? 0);
        if ($year < 2000 || $month < 1 || $month > 12) {
            return;
        }

        if ($replacePending) {
            $this->installments()->where('status', 'pending')->delete();
        }

        $collectedTotal = (float) $this->installments()
            ->where('status', 'collected')
            ->sum('amount');
        $remaining = round((float) $this->amount - $collectedTotal, 2);
        if ($remaining <= 0) {
            return;
        }

        $count = $this->resolveInstallmentCount($remaining);
        if ($count < 1) {
            $count = 1;
        }

        $base = round($remaining / $count, 2);
        $rows = [];
        $allocated = 0.0;
        $seqStart = (int) $this->installments()->max('sequence') + 1;

        for ($i = 0; $i < $count; $i++) {
            $amount = ($i === $count - 1)
                ? round($remaining - $allocated, 2)
                : $base;
            $allocated = round($allocated + $amount, 2);

            $y = $year;
            $m = $month + $i;
            while ($m > 12) {
                $m -= 12;
                $y++;
            }

            $rows[] = [
                'staff_advance_id' => $this->id,
                'sequence' => $seqStart + $i,
                'year' => $y,
                'month' => $m,
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows !== []) {
            StaffAdvanceInstallment::insert($rows);
        }

        $this->installment_count = $count + (int) $this->installments()->where('status', 'collected')->count();
        $last = end($rows);
        if ($last) {
            $this->expected_completion_date = Carbon::create($last['year'], $last['month'], 28)->endOfMonth();
        }
        $this->save();
    }

    public function resolveInstallmentCount(float $remainingAmount): int
    {
        return match ($this->repayment_method) {
            'installments' => max(1, (int) ($this->installment_count ?: 1)),
            'monthly_deduction' => max(
                1,
                (int) ceil($remainingAmount / max(0.01, (float) ($this->monthly_deduction_amount ?: $remainingAmount)))
            ),
            default => 1, // lump_sum
        };
    }

    public function pendingInstallmentFor(int $year, int $month): ?StaffAdvanceInstallment
    {
        return $this->installments()
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'pending')
            ->first();
    }
}
