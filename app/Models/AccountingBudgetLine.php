<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingBudgetLine extends Model
{
    protected $fillable = [
        'budget_id',
        'account_id',
        'budget_amount',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(AccountingBudget::class, 'budget_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
