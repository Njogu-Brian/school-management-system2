<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalBulkImport extends Model
{
    protected $fillable = [
        'batch_code',
        'original_filename',
        'uploaded_by',
        'status',
        'total_rows',
        'pending_rows',
        'approved_rows',
        'failed_rows',
        'invalid_rows',
        'credit_total',
        'debit_total',
        'notes',
        'approved_at',
    ];

    protected $casts = [
        'credit_total' => 'decimal:2',
        'debit_total' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(JournalBulkImportRow::class);
    }

    public function refreshCounts(): void
    {
        $rows = $this->rows()->get(['status', 'type', 'amount']);

        $this->total_rows = $rows->count();
        $this->pending_rows = $rows->where('status', 'pending')->count();
        $this->approved_rows = $rows->where('status', 'approved')->count();
        $this->failed_rows = $rows->where('status', 'failed')->count();
        $this->invalid_rows = $rows->where('status', 'invalid')->count();

        $approved = $rows->where('status', 'approved');
        $this->credit_total = round((float) $approved->where('type', 'credit')->sum('amount'), 2);
        $this->debit_total = round((float) $approved->where('type', 'debit')->sum('amount'), 2);

        if ($this->approved_rows > 0 && $this->pending_rows === 0) {
            $this->status = 'completed';
            $this->approved_at = $this->approved_at ?: now();
        } elseif ($this->approved_rows > 0) {
            $this->status = 'partial';
            $this->approved_at = $this->approved_at ?: now();
        } elseif ($this->status !== 'abandoned') {
            $this->status = 'preview';
        }

        $this->save();
    }

    public static function nextBatchCode(): string
    {
        $prefix = 'JBI-' . now()->format('Ymd');
        $seq = static::where('batch_code', 'like', $prefix . '-%')->count() + 1;

        return sprintf('%s-%03d', $prefix, $seq);
    }
}
