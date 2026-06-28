<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseStatementRecipientProfile extends Model
{
    protected $fillable = [
        'group_key',
        'display_name',
        'default_vendor_name',
        'transaction_type',
        'is_business_expense',
        'expense_category_id',
        'default_description',
        'updated_by',
    ];

    protected $casts = [
        'is_business_expense' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
