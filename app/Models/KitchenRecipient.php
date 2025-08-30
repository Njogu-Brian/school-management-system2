<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenRecipient extends Model
{
    protected $table = 'kitchen_recipients';

    protected $fillable = [
        'label',          // e.g. Chef, Upper Janitor, Lower Janitor
        'staff_id',       // FK to staff.id
        'classroom_ids',  // json array of classroom IDs for partial summaries
        'active',         // bool
    ];

    protected $casts = [
        'classroom_ids' => 'array',
        'active'        => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
