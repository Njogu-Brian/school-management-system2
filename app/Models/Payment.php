<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\{Student, Family, Invoice, PaymentMethod, User, PaymentAllocation, Receipt};

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaction_code',
        'public_token',
        'hashed_id',
        'receipt_number',
        'student_id',
        'family_id',
        'invoice_id',
        'amount',
        'allocated_amount',
        'unallocated_amount',
        'payment_method', // Keep for backward compatibility
        'payment_method_id',
        'payer_name',
        'payer_type',
        'narration',
        'payment_date',
        'receipt_date',
        'reversed',
        'reversed_by',
        'reversed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'receipt_date' => 'datetime',
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
            // Generate unique public token for receipt access (10 chars for SMS cost reduction)
            if (!$payment->public_token) {
                $payment->public_token = self::generatePublicToken();
            }
            // Generate hashed ID for secure URL access
            if (!$payment->hashed_id) {
                $payment->hashed_id = self::generateHashedId();
            }
            // Auto-set receipt_date if not provided (set to current timestamp)
            if (!$payment->receipt_date) {
                $payment->receipt_date = now();
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
     * Generate unique public token for receipt access (10 characters for SMS cost reduction)
     */
    public static function generatePublicToken(): string
    {
        do {
            // Generate 10 character alphanumeric token (case-sensitive for more combinations)
            $token = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 10);
        } while (self::where('public_token', $token)->exists());
        
        return $token;
    }

    /**
     * Generate hashed ID for secure URL access
     */
    public static function generateHashedId(): string
    {
        do {
            // Generate 10 character alphanumeric hash
            $hash = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 10);
        } while (self::where('hashed_id', $hash)->exists());
        
        return $hash;
    }

    /**
     * Get route key name - use ID for internal routes
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Resolve route binding - support both ID and hashed_id/public_token
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If field is explicitly set, use that
        if ($field === 'hashed_id') {
            return $this->where('hashed_id', $value)->firstOrFail();
        }
        if ($field === 'public_token') {
            return $this->where('public_token', $value)->firstOrFail();
        }
        
        // Otherwise, try numeric ID first (for internal routes)
        if (is_numeric($value)) {
            return $this->where('id', $value)->firstOrFail();
        }
        
        // Fallback to public_token if not numeric (for public receipt routes)
        return $this->where('public_token', $value)->firstOrFail();
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
