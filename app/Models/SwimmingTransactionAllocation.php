<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwimmingTransactionAllocation extends Model
{
    protected $fillable = [
        'bank_statement_transaction_id',
        'student_id',
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

    const STATUS_PENDING = 'pending';
    const STATUS_ALLOCATED = 'allocated';
    const STATUS_REVERSED = 'reversed';

    public function bankStatementTransaction(): BelongsTo
    {
        return $this->belongsTo(BankStatementTransaction::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAllocated(): bool
    {
        return $this->status === self::STATUS_ALLOCATED;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }
}
