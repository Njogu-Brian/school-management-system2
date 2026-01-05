<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryType extends Model
{
    protected $fillable = [
        'name', 'display_name', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }
}

