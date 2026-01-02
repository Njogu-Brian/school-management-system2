<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyStatementLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'term_id',
        'txn_date',
        'narration_raw',
        'txn_type',
        'votehead',
        'reference_number',
        'linked_invoice_ref',
        'channel',
        'txn_code',
        'amount_dr',
        'amount_cr',
        'running_balance',
        'confidence',
        'sequence_no',
        'metadata',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'amount_dr' => 'decimal:2',
        'amount_cr' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyFinanceImportBatch::class, 'batch_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(LegacyStatementTerm::class, 'term_id');
    }

    public function editHistory(): HasMany
    {
        return $this->hasMany(LegacyStatementLineEditHistory::class, 'line_id');
    }
}

