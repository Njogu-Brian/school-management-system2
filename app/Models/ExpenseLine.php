<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'category_id',
        'department',
        'cost_center',
        'description',
        'qty',
        'unit_cost',
        'tax_rate',
        'line_total',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $line) {
            $base = (float) $line->qty * (float) $line->unit_cost;
            $line->line_total = $base + ($base * ((float) $line->tax_rate / 100));
        });
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }
}
