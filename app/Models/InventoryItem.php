<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'category', 'inventory_type_id', 'brand', 'description', 'unit',
        'quantity', 'min_stock_level', 'unit_cost', 'location', 'is_active'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function requisitionItems()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function inventoryType()
    {
        return $this->belongsTo(InventoryType::class);
    }

    public function isLowStock()
    {
        return $this->quantity <= $this->min_stock_level;
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        });
    }
}
