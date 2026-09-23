<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityFeeAllocation extends Model
{
    public const STATUS_ALLOCATED = 'allocated';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'extra_income_item_id',
        'student_id',
        'bank_statement_transaction_id',
        'mpesa_c2b_transaction_id',
        'payment_id',
        'invoice_item_id',
        'amount',
        'status',
        'notes',
        'allocated_by',
        'allocated_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'allocated_at' => 'datetime',
    ];

    public function extraIncomeItem(): BelongsTo
    {
        return $this->belongsTo(ExtraIncomeItem::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }
}
