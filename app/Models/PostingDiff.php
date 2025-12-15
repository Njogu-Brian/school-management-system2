<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\{FeePostingRun, Student, Votehead, InvoiceItem};

class PostingDiff extends Model
{
    protected $fillable = [
        'posting_run_id',
        'student_id',
        'votehead_id',
        'action',
        'old_amount',
        'new_amount',
        'invoice_item_id',
        'source',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function postingRun(): BelongsTo
    {
        return $this->belongsTo(FeePostingRun::class, 'posting_run_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function isAdded(): bool
    {
        return $this->action === 'added';
    }

    public function isIncreased(): bool
    {
        return $this->action === 'increased';
    }

    public function isDecreased(): bool
    {
        return $this->action === 'decreased';
    }

    public function isUnchanged(): bool
    {
        return $this->action === 'unchanged';
    }

    public function getAmountChange(): float
    {
        if ($this->old_amount === null) {
            return $this->new_amount ?? 0;
        }
        return ($this->new_amount ?? 0) - $this->old_amount;
    }
}

