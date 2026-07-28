<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentWallet extends Model
{
    protected $fillable = [
        'parent_info_id',
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

    public function parentInfo(): BelongsTo
    {
        return $this->belongsTo(ParentInfo::class, 'parent_info_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(ParentWalletLedger::class);
    }

    public static function getOrCreateForParent(int $parentInfoId): self
    {
        return static::firstOrCreate(
            ['parent_info_id' => $parentInfoId],
            [
                'balance' => 0,
                'total_credited' => 0,
                'total_debited' => 0,
            ]
        );
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return (float) $this->balance >= $amount;
    }
}
