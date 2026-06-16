<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseStatementImport extends Model
{
    public const SOURCE_MPESA = 'mpesa';
    public const SOURCE_BANK = 'bank';

    public const STATUS_PARSED = 'parsed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uploaded_by',
        'source',
        'original_filename',
        'file_path',
        'period_start',
        'period_end',
        'account_name',
        'account_number',
        'status',
        'line_count',
        'outgoing_count',
        'outgoing_total',
        'confirmed_expense_total',
        'parse_error',
        'summary',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'line_count' => 'integer',
        'outgoing_count' => 'integer',
        'outgoing_total' => 'decimal:2',
        'confirmed_expense_total' => 'decimal:2',
        'summary' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseStatementLine::class, 'import_id');
    }
}
