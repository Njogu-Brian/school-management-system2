<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyStatementLineEditHistory extends Model
{
    protected $fillable = [
        'line_id',
        'batch_id',
        'edited_by',
        'before_values',
        'after_values',
        'changed_fields',
        'notes',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
        'changed_fields' => 'array',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(LegacyStatementLine::class, 'line_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegacyFinanceImportBatch::class, 'batch_id');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'edited_by');
    }
}
