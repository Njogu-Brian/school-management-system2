<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentVoucher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'voucher_no',
        'expense_id',
        'payee',
        'payment_method',
        'payment_date',
        'amount',
        'status',
        'prepared_by',
        'approved_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $voucher) {
            if (!$voucher->voucher_no) {
                $voucher->voucher_no = self::generateVoucherNo();
            }
        });
    }

    public static function generateVoucherNo(): string
    {
        do {
            $number = 'PV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (self::where('voucher_no', $number)->exists());

        return $number;
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExpensePayment::class, 'voucher_id');
    }
}
