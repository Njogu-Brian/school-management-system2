<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseStatementLine extends Model
{
    public const REVIEW_PENDING = 'pending';
    public const REVIEW_CONFIRMED = 'confirmed_expense';
    public const REVIEW_PERSONAL = 'personal';
    public const REVIEW_IGNORED = 'ignored';

    public const TYPE_SEND_MONEY = 'send_money';
    public const TYPE_POCHI = 'pochi';
    public const TYPE_BUY_GOODS = 'buy_goods';
    public const TYPE_PAYBILL = 'paybill';
    public const TYPE_FEE = 'fee';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'import_id',
        'receipt_no',
        'completed_at',
        'narration',
        'line_fingerprint',
        'withdrawn_amount',
        'paid_in_amount',
        'direction',
        'transaction_type',
        'is_transaction_fee',
        'recipient_name',
        'recipient_phone',
        'paybill_number',
        'account_reference',
        'merchant_reference',
        'group_key',
        'review_status',
        'expense_category_id',
        'expense_description',
        'expense_id',
        'raw_data',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'withdrawn_amount' => 'decimal:2',
        'paid_in_amount' => 'decimal:2',
        'is_transaction_fee' => 'boolean',
        'raw_data' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(ExpenseStatementImport::class, 'import_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function getDisplayAmountAttribute(): float
    {
        return (float) ($this->direction === 'out' ? $this->withdrawn_amount : $this->paid_in_amount);
    }

    public function getTransactionTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            self::TYPE_SEND_MONEY => 'Send Money',
            self::TYPE_POCHI => 'Pochi la Biashara',
            self::TYPE_BUY_GOODS => 'Buy Goods',
            self::TYPE_PAYBILL => 'Pay Bill',
            self::TYPE_FEE => 'Transaction Fee',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            default => 'Other',
        };
    }

    public static function fingerprint(
        ?string $receiptNo,
        ?\DateTimeInterface $completedAt,
        string $narration,
    ): string {
        $completed = $completedAt?->format('Y-m-d H:i:s') ?? '';

        return hash('sha256', implode('|', [
            (string) $receiptNo,
            $completed,
            trim($narration),
        ]));
    }
}
