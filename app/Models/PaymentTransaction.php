<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'student_id',
        'invoice_id',
        'payment_link_id',
        'initiated_by',
        'gateway',
        'transaction_id',
        'mpesa_receipt_number',
        'phone_number',
        'account_reference',
        'reference',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'webhook_data',
        'failure_reason',
        'admin_notes',
        'paid_at',
        'mpesa_transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'webhook_data' => 'array',
        'paid_at' => 'datetime',
        'mpesa_transaction_date' => 'datetime',
    ];

    /**
     * Get the student for this transaction
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the invoice for this transaction
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the payment link for this transaction
     */
    public function paymentLink(): BelongsTo
    {
        return $this->belongsTo(PaymentLink::class);
    }

    /**
     * Get the user who initiated this transaction
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'cancelled']);
    }

    /**
     * Generate unique reference
     */
    public static function generateReference(): string
    {
        return 'PAY-' . strtoupper(uniqid()) . '-' . time();
    }
}

