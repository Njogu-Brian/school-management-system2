<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\{Payment, InvoiceItem, User};

class PaymentAllocation extends Model
{
    protected $fillable = [
        'payment_id',
        'invoice_item_id',
        'amount',
        'allocated_at',
        'allocated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    protected static function booted(): void
    {
        $recalcPaymentTotals = function (self $allocation): void {
            try {
                $payment = $allocation->relationLoaded('payment')
                    ? $allocation->payment
                    : Payment::find($allocation->payment_id);

                if ($payment) {
                    $payment->updateAllocationTotals();
                }
            } catch (\Throwable $e) {
                // Best-effort: never block allocation CRUD due to a totals refresh issue
                \Log::warning('Failed to refresh payment allocation totals', [
                    'payment_id' => $allocation->payment_id,
                    'invoice_item_id' => $allocation->invoice_item_id,
                    'error' => $e->getMessage(),
                ]);
            }
        };

        static::created($recalcPaymentTotals);
        static::updated($recalcPaymentTotals);
        static::deleted($recalcPaymentTotals);
    }
}

