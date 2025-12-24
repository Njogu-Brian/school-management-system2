<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\{Student, Family, AcademicYear, Term, FeePostingRun, User, InvoiceItem, Payment, CreditNote, DebitNote, FeeConcession, PaymentAllocation};

class Invoice extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'student_id',
        'family_id',
        'academic_year_id',
        'term_id',
        'year', // Keep for backward compatibility during migration
        'term', // Keep for backward compatibility during migration
        'invoice_number',
        'hashed_id',
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
        'archived_at' => 'datetime',
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
        
        // Calculate total from active items (amount - discount_amount)
        // Note: Credit/debit notes modify item amounts directly, so they're already included
        $this->total = $this->items()
            ->where('status', 'active')
            ->get()
            ->sum(function($item) {
                return $item->amount - ($item->discount_amount ?? 0);
            });
        
        // Subtract invoice-level discount
        $this->total = max(0, $this->total - ($this->discount_amount ?? 0));
        
        // Calculate paid amount from allocations
        $this->paid_amount = $this->items()
            ->join('payment_allocations', 'invoice_items.id', '=', 'payment_allocations.invoice_item_id')
            ->where('invoice_items.invoice_id', $this->id)
            ->sum('payment_allocations.amount');
        
        // Calculate balance (total already has discounts and credit/debit adjustments in item amounts)
        $this->balance = $this->total - $this->paid_amount;
        
        // Ensure balance is never negative
        if ($this->balance < 0) {
            $this->balance = 0;
        }
        
        // Update status
        if ($this->balance <= 0 && $this->paid_amount > 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }
        
        $this->save();
        
        // Auto-update payment statuses and auto-allocate unallocated payments
        $this->updatePaymentStatuses();
        $this->autoAllocateUnallocatedPayments();
    }
    
    /**
     * Update payment statuses for all payments related to this invoice
     */
    protected function updatePaymentStatuses(): void
    {
        // Get all payments that have allocations to this invoice's items
        $paymentIds = \App\Models\PaymentAllocation::whereHas('invoiceItem', function($q) {
            $q->where('invoice_id', $this->id);
        })->pluck('payment_id')->unique();
        
        foreach ($paymentIds as $paymentId) {
            $payment = \App\Models\Payment::find($paymentId);
            if ($payment) {
                $payment->updateStatus();
            }
        }
    }
    
    /**
     * Auto-allocate unallocated payments for this student when invoice is updated
     * Only runs if auto-allocation is enabled (to prevent infinite loops)
     */
    protected function autoAllocateUnallocatedPayments(): void
    {
        // Prevent infinite loops - only auto-allocate if flag is explicitly enabled
        // Default is false to prevent automatic allocation on every invoice update
        if (!app()->bound('auto_allocating') || !app('auto_allocating')) {
            return;
        }
        
        // Get all unallocated or partially allocated payments for this student
        $payments = \App\Models\Payment::where('student_id', $this->student_id)
            ->where('reversed', false)
            ->where(function($q) {
                $q->where('unallocated_amount', '>', 0)
                  ->orWhereRaw('amount > allocated_amount');
            })
            ->get();
        
        foreach ($payments as $payment) {
            // Check if there are unpaid invoice items for this student
            $unpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) {
                $q->where('student_id', $this->student_id)
                  ->where('status', '!=', 'paid');
            })
            ->where('status', 'active')
            ->get()
            ->filter(function($item) {
                return $item->getBalance() > 0;
            });
            
            if ($unpaidItems->isNotEmpty() && $payment->unallocated_amount > 0) {
                // Auto-allocate using PaymentAllocationService
                try {
                    // Temporarily disable auto-allocation to prevent recursion
                    $originalFlag = app('auto_allocating');
                    app()->instance('auto_allocating', false);
                    \App\Services\PaymentAllocationService::autoAllocate($payment, $this->student_id);
                    app()->instance('auto_allocating', $originalFlag);
                } catch (\Exception $e) {
                    // Log but don't fail - auto-allocation is best effort
                    \Illuminate\Support\Facades\Log::warning('Auto-allocation failed', [
                        'payment_id' => $payment->id,
                        'student_id' => $this->student_id,
                        'error' => $e->getMessage()
                    ]);
                    app()->instance('auto_allocating', $originalFlag ?? false);
                }
            }
        }
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid' || $this->balance <= 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->isPaid();
    }

    /**
     * Generate hashed ID for secure URL access
     */
    public static function generateHashedId(): string
    {
        do {
            $hash = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 10);
        } while (self::where('hashed_id', $hash)->exists());
        
        return $hash;
    }

    /**
     * Get route key name - use ID for internal routes, hashed_id for public routes
     */
    public function getRouteKeyName()
    {
        // For internal routes, use 'id' (default)
        // For public routes, explicitly use 'hashed_id' in route definition
        return 'id';
    }

    /**
     * Resolve route binding - support both ID and hashed_id
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // If field is explicitly set to 'hashed_id', use that
        if ($field === 'hashed_id') {
            return $this->where('hashed_id', $value)->firstOrFail();
        }
        
        // Otherwise, try numeric ID first (for internal routes)
        if (is_numeric($value)) {
            return $this->where('id', $value)->firstOrFail();
        }
        
        // Fallback to hashed_id if not numeric
        return $this->where('hashed_id', $value)->firstOrFail();
    }

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invoice) {
            if (!$invoice->hashed_id) {
                $invoice->hashed_id = self::generateHashedId();
            }
        });
    }
}
