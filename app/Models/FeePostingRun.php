<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\{AcademicYear, Term, User, Invoice, InvoiceItem};

class FeePostingRun extends Model
{
    use HasFactory;
    protected $fillable = [
        'academic_year_id',
        'term_id',
        'run_type',
        'status',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'filters_applied',
        'items_posted_count',
        'notes',
        'is_active',
        'total_amount_posted',
        'total_students_affected',
    ];

    protected $casts = [
        'filters_applied' => 'array',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'items_posted_count' => 'integer',
        'is_active' => 'boolean',
        'total_amount_posted' => 'decimal:2',
        'total_students_affected' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function diffs(): HasMany
    {
        return $this->hasMany(PostingDiff::class, 'posting_run_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'posting_run_id');
    }

    public function isDryRun(): bool
    {
        return $this->run_type === 'dry_run';
    }

    public function isCommit(): bool
    {
        return $this->run_type === 'commit';
    }

    public function isReversal(): bool
    {
        return $this->run_type === 'reversal';
    }

    public function canBeReversed(): bool
    {
        return $this->status === 'completed' && $this->run_type === 'commit' && !$this->reversed_at;
    }
}

