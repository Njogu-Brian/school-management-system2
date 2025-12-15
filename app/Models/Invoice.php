<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\{Student, Family, AcademicYear, Term, FeePostingRun, User, InvoiceItem, Payment, CreditNote, DebitNote, FeeConcession, PaymentAllocation};

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id',
        'family_id',
        'academic_year_id',
        'term_id',
        'year', // Keep for backward compatibility during migration
        'term', // Keep for backward compatibility during migration
        'invoice_number',
        'total',
        'paid_amount',
        'balance',
        'discount_amount',
        'status',
        'due_date',
        'issued_date',
        'reversed_at',
        'posting_run_id',
        'posted_by',
        'posted_at',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'due_date' => 'date',
        'issued_date' => 'date',
        'reversed_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function postingRun(): BelongsTo
    {
        return $this->belongsTo(FeePostingRun::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class);
    }

    public function feeConcessions(): HasMany
    {
        return $this->hasMany(FeeConcession::class);
    }

    /**
     * Calculate and update invoice totals
     */
    public function recalculate(): void
    {
        $this->refresh();
        
        // Calculate total from active items only
        $this->total = $this->items()
            ->where('status', 'active')
            ->sum('amount');
        
        // Calculate paid amount from allocations
        $this->paid_amount = $this->items()
            ->join('payment_allocations', 'invoice_items.id', '=', 'payment_allocations.invoice_item_id')
            ->where('invoice_items.invoice_id', $this->id)
            ->sum('payment_allocations.amount');
        
        // Calculate balance
        $this->balance = $this->total - $this->paid_amount - $this->discount_amount;
        
        // Update status
        if ($this->balance <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }
        
        $this->save();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->balance <= 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isPaid();
    }
}
