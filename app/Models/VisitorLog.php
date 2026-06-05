<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorLog extends Model
{
    protected $fillable = [
        'visitor_name',
        'phone',
        'id_number',
        'organization',
        'purpose',
        'host_name',
        'host_staff_id',
        'badge_number',
        'checked_in_at',
        'checked_out_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function hostStaff()
    {
        return $this->belongsTo(Staff::class, 'host_staff_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOnSite(): bool
    {
        return $this->checked_out_at === null;
    }
}
