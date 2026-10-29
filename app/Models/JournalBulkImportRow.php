<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalBulkImportRow extends Model
{
    protected $fillable = [
        'journal_bulk_import_id',
        'uid',
        'line',
        'admission_number',
        'student_id',
        'student_name',
        'votehead_id',
        'votehead_name',
        'effective_date',
        'type',
        'type_label',
        'year',
        'term',
        'reason',
        'amount',
        'before_amount',
        'new_amount',
        'invoice_balance_before',
        'invoice_balance_after',
        'invoice_id',
        'journal_id',
        'status',
        'message',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'amount' => 'decimal:2',
        'before_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'invoice_balance_before' => 'decimal:2',
        'invoice_balance_after' => 'decimal:2',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(JournalBulkImport::class, 'journal_bulk_import_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function adjustmentSigned(): float
    {
        $amount = (float) $this->amount;
        return $this->type === 'debit' ? $amount : -$amount;
    }
}
