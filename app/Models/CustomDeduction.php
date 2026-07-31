<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CustomDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'deduction_type_id',
        'staff_advance_id',
        'amount',
        'effective_from',
        'effective_to',
        'frequency',
        'applicable_months',
        'installment_number',
        'total_installments',
        'total_amount',
        'amount_deducted',
        'status',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_deducted' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'applicable_months' => 'array',
        'installment_number' => 'integer',
        'total_installments' => 'integer',
    ];

    public static function monthLabels(): array
    {
        return [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function deductionType()
    {
        return $this->belongsTo(DeductionType::class);
    }

    public function staffAdvance()
    {
        return $this->belongsTo(StaffAdvance::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if deduction is currently active
     */
    public function isCurrentlyActive()
    {
        $now = Carbon::now();
        return $this->status === 'active' 
            && $this->effective_from <= $now 
            && ($this->effective_to === null || $this->effective_to >= $now);
    }

    /**
     * Record deduction
     */
    public function recordDeduction($amount)
    {
        $this->amount_deducted += $amount;
        
        // Update installment number if applicable
        if ($this->total_installments) {
            $this->installment_number = min($this->installment_number + 1, $this->total_installments);
        }
        
        // Check if completed
        if ($this->total_amount && $this->amount_deducted >= $this->total_amount) {
            $this->status = 'completed';
        }
        
        $this->save();
    }

    /**
     * Reverse a previously recorded deduction (e.g. cancelled payslip).
     */
    public function reverseDeduction($amount)
    {
        $amount = min((float) $amount, (float) $this->amount_deducted);
        if ($amount <= 0) {
            return;
        }

        $this->amount_deducted = max(0, (float) $this->amount_deducted - $amount);

        if ($this->total_installments && $this->installment_number > 0) {
            $this->installment_number = max(0, $this->installment_number - 1);
        }

        if ($this->status === 'completed' && (! $this->total_amount || $this->amount_deducted < $this->total_amount)) {
            $this->status = 'active';
        }

        $this->save();
    }

    /**
     * Check if should be deducted this month
     */
    public function shouldDeductThisMonth($year, $month)
    {
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        $periodStart = Carbon::create($year, $month, 1);
        $periodEnd = $periodStart->copy()->endOfMonth();

        // Check if effective period overlaps with payroll period
        if ($this->effective_from > $periodEnd || ($this->effective_to && $this->effective_to < $periodStart)) {
            return false;
        }

        // Check frequency
        switch ($this->frequency) {
            case 'one_time':
                return $this->amount_deducted == 0;
            
            case 'monthly':
                return true;
            
            case 'quarterly':
                return $month % 3 == 1; // First month of quarter
            
            case 'yearly':
                return $month == 1; // January

            case 'custom_months':
                $months = collect($this->applicable_months ?? [])
                    ->map(fn ($m) => (int) $m)
                    ->filter(fn ($m) => $m >= 1 && $m <= 12)
                    ->all();

                return in_array((int) $month, $months, true);
            
            default:
                return true;
        }
    }

    /**
     * Scope: Active deductions
     */
    public function scopeActive($query)
    {
        $now = Carbon::now();
        return $query->where('status', 'active')
            ->where('effective_from', '<=', $now)
            ->where(function($q) use ($now) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $now);
            });
    }
}
