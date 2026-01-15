<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BankStatementTransaction extends Model
{
    protected $fillable = [
        'bank_account_id',
        'statement_file_path',
        'bank_type',
        'transaction_date',
        'amount',
        'transaction_type',
        'reference_number',
        'description',
        'phone_number',
        'payer_name',
        'matched_admission_number',
        'matched_student_name',
        'matched_phone_number',
        'student_id',
        'family_id',
        'match_status',
        'match_confidence',
        'match_notes',
        'status',
        'confirmed_by',
        'confirmed_at',
        'payment_id',
        'payment_created',
        'is_duplicate',
        'duplicate_of_payment_id',
        'is_archived',
        'archived_at',
        'archived_by',
        'is_shared',
        'shared_allocations',
        'raw_data',
        'notes',
        'created_by',
        'is_swimming_transaction',
        'swimming_allocated_amount',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'match_confidence' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'archived_at' => 'datetime',
        'payment_created' => 'boolean',
        'is_shared' => 'boolean',
        'is_duplicate' => 'boolean',
        'is_archived' => 'boolean',
        'shared_allocations' => 'array',
        'raw_data' => 'array',
        'is_swimming_transaction' => 'boolean',
        'swimming_allocated_amount' => 'decimal:2',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function duplicateOfPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'duplicate_of_payment_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function isAutoAssigned(): bool
    {
        return $this->match_status === 'matched' && $this->match_confidence >= 0.85;
    }

    public function archive(?int $userId = null): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $userId ?? auth()->id(),
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
            'archived_by' => null,
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isMatched(): bool
    {
        return in_array($this->match_status, ['matched', 'manual']);
    }

    public function hasMultipleMatches(): bool
    {
        return $this->match_status === 'multiple_matches';
    }

    public function confirm(?int $userId = null): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_by' => $userId ?? auth()->id(),
            'confirmed_at' => now(),
        ]);
    }

    public function reject(?int $userId = null): void
    {
        $this->update([
            'status' => 'rejected',
            'confirmed_by' => $userId ?? auth()->id(),
            'confirmed_at' => now(),
        ]);
    }

    public function getSharedAllocationsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    public function setSharedAllocationsAttribute($value)
    {
        $this->attributes['shared_allocations'] = is_array($value) ? json_encode($value) : $value;
    }

    public function swimmingAllocations()
    {
        return $this->hasMany(SwimmingTransactionAllocation::class);
    }

    public function isSwimmingTransaction(): bool
    {
        return $this->is_swimming_transaction ?? false;
    }
}
