<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'parent_id',
        'normal_balance',
        'is_postable',
        'is_system',
        'is_active',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'is_postable' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public static function types(): array
    {
        return [
            self::TYPE_ASSET => 'Assets',
            self::TYPE_LIABILITY => 'Liabilities',
            self::TYPE_EQUITY => 'Equity',
            self::TYPE_REVENUE => 'Revenue',
            self::TYPE_EXPENSE => 'Expenses',
        ];
    }

    public static function defaultNormalBalance(string $type): string
    {
        return in_array($type, [self::TYPE_ASSET, self::TYPE_EXPENSE], true) ? 'dr' : 'cr';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function expenseCategories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function fullName(): string
    {
        return $this->code . ' — ' . $this->name;
    }

    public function isHeader(): bool
    {
        return ! $this->is_postable || $this->children()->exists();
    }
}
