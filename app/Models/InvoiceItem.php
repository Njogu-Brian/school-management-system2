<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\{Invoice, Votehead, FeePostingRun, PaymentAllocation, CreditNote, DebitNote};

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'invoice_id',
        'votehead_id',
        'amount',
        'discount_amount',
        'original_amount',
        'status',
        'effective_date',
        'source',
        'posting_run_id',
        'posted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'effective_date' => 'date',
        'posted_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function postingRun(): BelongsTo
    {
        return $this->belongsTo(FeePostingRun::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class);
    }

    /**
     * Get allocated amount from payment allocations
     */
    public function getAllocatedAmount(): float
    {
        return $this->allocations()->sum('amount');
    }

    /**
     * Get balance (amount - discount - allocated)
     */
    public function getBalance(): float
    {
        return $this->amount - $this->discount_amount - $this->getAllocatedAmount();
    }

    public function isFullyPaid(): bool
    {
        return $this->getBalance() <= 0;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
