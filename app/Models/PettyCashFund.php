<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashFund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'account_id',
        'custodian_id',
        'imprest_amount',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'imprest_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(PettyCashVoucher::class);
    }

    public function postedBalance(): float
    {
        $fundAccountId = $this->account_id;
        $debits = (float) JournalLine::query()
            ->where('account_id', $fundAccountId)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', JournalEntry::STATUS_POSTED))
            ->sum('debit');
        $credits = (float) JournalLine::query()
            ->where('account_id', $fundAccountId)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', JournalEntry::STATUS_POSTED))
            ->sum('credit');

        return round($debits - $credits, 2);
    }
}
