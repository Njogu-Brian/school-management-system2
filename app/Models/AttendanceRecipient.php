<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecipient extends Model
{
    protected $fillable = [
        'label', 'staff_id', 'classroom_ids', 'active',
    ];

    protected $casts = [
        'classroom_ids' => 'array',
        'active' => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
