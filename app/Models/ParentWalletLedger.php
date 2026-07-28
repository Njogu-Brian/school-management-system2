<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentWalletLedger extends Model
{
    protected $table = 'parent_wallet_ledger';

    public const TYPE_DEPOSIT = 'deposit';
    public const TYPE_FEE_ALLOCATION = 'fee_allocation';
    public const TYPE_SPEND = 'spend';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'parent_wallet_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(ParentWallet::class, 'parent_wallet_id');
    }
}
