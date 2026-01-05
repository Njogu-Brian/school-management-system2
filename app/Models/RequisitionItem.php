<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequisitionItem extends Model
{
    protected $fillable = [
        'requisition_id', 'inventory_item_id', 'requirement_type_id',
        'item_name', 'brand', 'quantity_requested', 'quantity_approved',
        'quantity_issued', 'issued_by', 'issued_at', 'unit', 'purpose'
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'quantity_approved' => 'decimal:2',
        'quantity_issued' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function requisition()
    {
        return $this->belongsTo(Requisition::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function requirementType()
    {
        return $this->belongsTo(RequirementType::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'issued_by');
    }
}
