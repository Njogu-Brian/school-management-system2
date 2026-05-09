<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'expense_no',
        'source_type',
        'vendor_id',
        'requested_by',
        'expense_date',
        'due_date',
        'currency',
        'subtotal',
        'tax_total',
        'total',
        'status',
        'notes',
        'submitted_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $expense) {
            if (!$expense->expense_no) {
                $expense->expense_no = self::generateExpenseNo();
            }
        });
    }

    public static function generateExpenseNo(): string
    {
        do {
            $number = 'EXP-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('expense_no', $number)->exists());

        return $number;
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ExpenseLine::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ExpenseApproval::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(PaymentVoucher::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->lines()->sum(\DB::raw('qty * unit_cost'));
        $tax = (float) $this->lines()->sum(\DB::raw('(qty * unit_cost) * (tax_rate / 100)'));
        $this->subtotal = $subtotal;
        $this->tax_total = $tax;
        $this->total = $subtotal + $tax;
    }
}
