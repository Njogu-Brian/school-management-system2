<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SwimmingLedger extends Model
{
    protected $table = 'swimming_ledger';

    protected $fillable = [
        'student_id',
        'type',
        'amount',
        'balance_after',
        'source',
        'source_id',
        'source_type',
        'swimming_attendance_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    const TYPE_CREDIT = 'credit';
    const TYPE_DEBIT = 'debit';

    const SOURCE_TRANSACTION = 'transaction';
    const SOURCE_BANK_TRANSACTION = 'bank_transaction';
    const SOURCE_OPTIONAL_FEE = 'optional_fee';
    const SOURCE_ADJUSTMENT = 'adjustment';
    const SOURCE_ATTENDANCE = 'attendance';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function swimmingAttendance(): BelongsTo
    {
        return $this->belongsTo(SwimmingAttendance::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }
}
