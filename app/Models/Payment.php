<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\{Student, Family, Invoice, PaymentMethod, User, PaymentAllocation, Receipt};

class Payment extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'transaction_code',
        'public_token',
        'hashed_id',
        'receipt_number',
        'student_id',
        'family_id',
        'invoice_id',
        'payment_link_id',
        'payment_transaction_id',
        'amount',
        'allocated_amount',
        'unallocated_amount',
        'payment_method', // Keep for backward compatibility
        'payment_method_id',
        'payment_channel',
        'mpesa_receipt_number',
        'mpesa_phone_number',
        'payer_name',
        'payer_type',
        'narration',
        'payment_date',
        'receipt_date',
        'reversed',
        'reversed_by',
        'reversed_at',
        'bulk_sent_channels',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'receipt_date' => 'datetime',
        'reversed' => 'boolean',
        'reversed_at' => 'datetime',
        'archived_at' => 'datetime',
        'bulk_sent_channels' => 'array',
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

    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
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
        
        // Auto-update status
        $this->updateStatus();
    }
    
    /**
     * Update payment status based on allocation
     */
    public function updateStatus(): void
    {
        if ($this->reversed) {
            return; // Don't update status for reversed payments
        }
        
        // Status is determined by allocation:
        // - If fully allocated: "Completed"
        // - If partially allocated: "Partial"
        // - If unallocated: "Unallocated" (overpayment/carry forward)
        
        // Note: We don't store status in DB, it's calculated on-the-fly
        // But we can add a computed attribute if needed
        $this->save(); // Save any allocation updates
    }
    
    /**
     * Get payment status (computed attribute)
     * Note: If status column exists in DB, this will override it
     */
    public function getStatusAttribute(): string
    {
        // Check if status column exists and has a value (for backward compatibility)
        if (isset($this->attributes['status']) && $this->attributes['status']) {
            return $this->attributes['status'];
        }
        
        // Compute status from allocation
        if ($this->reversed) {
            return 'reversed';
        }
        
        if ($this->allocated_amount >= $this->amount) {
            return 'completed';
        } elseif ($this->allocated_amount > 0) {
            return 'partial';
        } else {
            return 'unallocated';
        }
    }

    public function isFullyAllocated(): bool
    {
        return $this->allocated_amount >= $this->amount;
    }

    public function hasOverpayment(): bool
    {
        return $this->unallocated_amount > 0;
    }

    /**
     * Check if payment has been bulk sent via specific channels
     */
    public function hasBeenBulkSent(array $channels): bool
    {
        $bulkSent = $this->bulk_sent_channels ?? [];
        if (empty($bulkSent)) {
            return false;
        }
        
        // Check if all requested channels have been bulk sent
        foreach ($channels as $channel) {
            if (!in_array($channel, $bulkSent)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Mark channels as bulk sent
     */
    public function markBulkSent(array $channels): void
    {
        $bulkSent = $this->bulk_sent_channels ?? [];
        $updated = false;
        
        foreach ($channels as $channel) {
            if (!in_array($channel, $bulkSent)) {
                $bulkSent[] = $channel;
                $updated = true;
            }
        }
        
        if ($updated) {
            $this->bulk_sent_channels = $bulkSent;
            $this->save();
        }
    }

    /**
     * Get human-readable payment channel name
     */
    public function getPaymentChannelNameAttribute(): string
    {
        $channels = [
            'stk_push' => 'M-PESA STK Push',
            'payment_link' => 'Payment Link',
            'paybill_manual' => 'M-PESA Paybill (Manual Entry)',
            'admin_entry' => 'Admin Entry',
            'mobile_app' => 'Mobile App',
            'online_portal' => 'Online Portal',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'cheque' => 'Cheque',
        ];

        return $channels[$this->payment_channel] ?? $this->payment_channel ?? 'Not Specified';
    }

    /**
     * Check if payment was made via M-PESA
     */
    public function isMpesaPayment(): bool
    {
        return in_array($this->payment_channel, ['stk_push', 'payment_link', 'paybill_manual'])
            || !empty($this->mpesa_receipt_number);
    }

    /**
     * Get payment source icon
     */
    public function getPaymentSourceIcon(): string
    {
        $icons = [
            'stk_push' => '<i class="fas fa-mobile-alt text-success"></i>',
            'payment_link' => '<i class="fas fa-link text-primary"></i>',
            'paybill_manual' => '<i class="fas fa-phone text-info"></i>',
            'admin_entry' => '<i class="fas fa-user-shield text-warning"></i>',
            'mobile_app' => '<i class="fas fa-mobile text-purple"></i>',
            'online_portal' => '<i class="fas fa-globe text-cyan"></i>',
            'bank_transfer' => '<i class="fas fa-university text-primary"></i>',
            'cash' => '<i class="fas fa-money-bill-wave text-success"></i>',
            'cheque' => '<i class="fas fa-file-invoice-dollar text-info"></i>',
        ];

        return $icons[$this->payment_channel] ?? '<i class="fas fa-receipt text-secondary"></i>';
    }
}
