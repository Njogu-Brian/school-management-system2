<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentWalletSavingPlan extends Model
{
    protected $fillable = [
        'parent_info_id',
        'user_id',
        'amount',
        'frequency',
        'day_of_week',
        'remind_at_time',
        'timezone',
        'next_remind_at',
        'active',
        'label',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'day_of_week' => 'integer',
        'next_remind_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function parentInfo(): BelongsTo
    {
        return $this->belongsTo(ParentInfo::class, 'parent_info_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
