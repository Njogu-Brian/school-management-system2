<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Requisition extends Model
{
    protected $fillable = [
        'requisition_number', 'requested_by', 'approved_by', 'type',
        'purpose', 'status', 'rejection_reason',
        'requested_at', 'approved_at', 'fulfilled_at'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($requisition) {
            if (!$requisition->requisition_number) {
                $requisition->requisition_number = 'REQ-' . date('Y') . '-' . strtoupper(Str::random(8));
            }
        });
    }

    public function requestedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(RequisitionItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}
