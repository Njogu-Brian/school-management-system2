<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountTemplate extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'discount_type',
        'type',
        'frequency',
        'scope',
        'value',
        'sibling_rules',
        'reason',
        'description',
        'end_date',
        'requires_approval',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'end_date' => 'date',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'value' => 'decimal:2',
        'sibling_rules' => 'array', // {"2": 5, "3": 10, "4": 15} - child position => discount percentage
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FeeConcession::class, 'discount_template_id');
    }

    /**
     * Calculate discount amount for a given fee amount
     */
    public function calculateDiscount($feeAmount)
    {
        if ($this->type === 'percentage') {
            return ($feeAmount * $this->value) / 100;
        }
        return min($this->value, $feeAmount); // Fixed amount, but can't exceed fee
    }

    /**
     * Get discount percentage for a specific child position (for sibling discounts)
     * Returns the discount percentage based on sibling_rules or calculates from value
     */
    public function getDiscountForChildPosition(int $childPosition): float
    {
        // If sibling_rules are defined, use them
        if ($this->sibling_rules && isset($this->sibling_rules[$childPosition])) {
            return (float) $this->sibling_rules[$childPosition];
        }
        
        // Otherwise, calculate based on template value and position
        // Default: 2nd child = value, 3rd = value*2, 4th = value*3, etc.
        if ($this->type === 'percentage') {
            return $this->value * ($childPosition - 1); // 2nd = 1x, 3rd = 2x, etc.
        }
        
        // For fixed amount, use the value as-is (or could multiply by position)
        return $this->value;
    }
}
