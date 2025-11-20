<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_item_id', 'user_id', 'student_requirement_id', 'requisition_id',
        'type', 'quantity', 'unit_cost', 'notes', 'reference_number'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function studentRequirement()
    {
        return $this->belongsTo(StudentRequirement::class);
    }

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::created(function ($transaction) {
            $item = $transaction->inventoryItem;
            if ($transaction->type === 'in') {
                $item->quantity += $transaction->quantity;
            } elseif ($transaction->type === 'out') {
                $item->quantity -= $transaction->quantity;
            } elseif ($transaction->type === 'adjustment') {
                $item->quantity = $transaction->quantity;
            }
            $item->save();
        });
    }
}
