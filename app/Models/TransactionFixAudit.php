<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionFixAudit extends Model
{
    protected $table = 'transaction_fix_audit';

    protected $fillable = [
        'fix_type',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'reason',
        'applied',
        'reversed',
        'applied_at',
        'reversed_at',
        'applied_by',
        'reversed_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'applied' => 'boolean',
        'reversed' => 'boolean',
        'applied_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function appliedBy()
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
