<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyLedgerPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'term_id',
        'line_id',
        'target_type',
        'target_id',
        'hash',
        'status',
        'error_message',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyFinanceImportBatch::class, 'batch_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(LegacyStatementTerm::class, 'term_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(LegacyStatementLine::class, 'line_id');
    }
}

