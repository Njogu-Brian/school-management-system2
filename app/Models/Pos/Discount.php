<?php

namespace App\Models\Pos;

use App\Models\Academics\Classroom;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'pos_discounts';

    protected $fillable = [
        'name', 'code', 'type', 'value', 'scope', 'category',
        'product_ids', 'classroom_id', 'min_purchase_amount',
        'min_quantity', 'start_date', 'end_date', 'usage_limit',
        'usage_count', 'per_user_limit', 'is_active', 'description'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'min_quantity' => 'integer',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_user_limit' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'product_ids' => 'array',
        'is_active' => 'boolean',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount($amount, $quantity = 1): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->min_purchase_amount && $amount < $this->min_purchase_amount) {
            return 0;
        }

        if ($this->min_quantity && $quantity < $this->min_quantity) {
            return 0;
        }

        if ($this->type === 'percentage') {
            return ($amount * $this->value) / 100;
        } elseif ($this->type === 'fixed') {
            return min($this->value, $amount);
        }

        return 0;
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            });
    }
}



