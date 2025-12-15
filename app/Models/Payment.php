<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\{Student, Family, Invoice, PaymentMethod, BankAccount, User, PaymentAllocation, Receipt};

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaction_code',
        'receipt_number',
        'student_id',
        'family_id',
        'invoice_id',
        'amount',
        'allocated_amount',
        'unallocated_amount',
        'payment_method', // Keep for backward compatibility
        'payment_method_id',
        'reference',
        'bank_account_id',
        'payer_name',
        'payer_type',
        'narration',
        'payment_date',
        'reversed',
        'reversed_by',
        'reversed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'reversed' => 'boolean',
        'reversed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (!$payment->transaction_code) {
                $payment->transaction_code = self::generateTransactionCode();
            }
            if (!$payment->receipt_number) {
                $payment->receipt_number = \App\Services\DocumentNumberService::generate('receipt', 'RCPT');
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    /**
     * Generate unique transaction code
     */
    public static function generateTransactionCode(): string
    {
        do {
            $code = 'TXN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -8));
        } while (self::where('transaction_code', $code)->exists());
        
        return $code;
    }

    /**
     * Calculate allocated amount from allocations
     */
    public function calculateAllocatedAmount(): float
    {
        return $this->allocations()->sum('amount');
    }

    /**
     * Calculate unallocated (overpayment) amount
     */
    public function calculateUnallocatedAmount(): float
    {
        return max(0, $this->amount - $this->allocated_amount);
    }

    /**
     * Update allocation totals
     */
    public function updateAllocationTotals(): void
    {
        $this->allocated_amount = $this->calculateAllocatedAmount();
        $this->unallocated_amount = $this->calculateUnallocatedAmount();
        $this->save();
    }

    public function isFullyAllocated(): bool
    {
        return $this->allocated_amount >= $this->amount;
    }

    public function hasOverpayment(): bool
    {
        return $this->unallocated_amount > 0;
    }
}
