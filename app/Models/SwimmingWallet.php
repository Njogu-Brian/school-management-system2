<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SwimmingWallet extends Model
{
    protected $fillable = [
        'student_id',
        'balance',
        'total_credited',
        'total_debited',
        'last_transaction_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_credited' => 'decimal:2',
        'total_debited' => 'decimal:2',
        'last_transaction_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(SwimmingLedger::class, 'student_id', 'student_id');
    }

    /**
     * Get or create wallet for a student
     */
    public static function getOrCreateForStudent(int $studentId): self
    {
        return static::firstOrCreate(
            ['student_id' => $studentId],
            [
                'balance' => 0,
                'total_credited' => 0,
                'total_debited' => 0,
            ]
        );
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}
