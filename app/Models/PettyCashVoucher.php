<?php

namespace App\Models;

use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashVoucher extends Model
{
    use SoftDeletes;

    public const TYPE_DISBURSEMENT = 'disbursement';
    public const TYPE_REPLENISHMENT = 'replenishment';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'voucher_no',
        'petty_cash_fund_id',
        'voucher_type',
        'voucher_date',
        'payee',
        'description',
        'amount',
        'expense_category_id',
        'account_id',
        'status',
        'prepared_by',
        'approved_by',
        'journal_entry_id',
        'reference_no',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $voucher) {
            if (! $voucher->voucher_no) {
                $voucher->voucher_no = DocumentNumberService::generatePettyCashVoucher();
            }
        });
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(PettyCashFund::class, 'petty_cash_fund_id');
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public static function types(): array
    {
        return [
            self::TYPE_DISBURSEMENT => 'Disbursement',
            self::TYPE_REPLENISHMENT => 'Replenishment',
        ];
    }
}
