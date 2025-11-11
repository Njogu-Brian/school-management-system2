<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'max_days',
        'is_paid',
        'requires_approval',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'max_days' => 'integer',
    ];

    public function leaveBalances()
    {
        return $this->hasMany(StaffLeaveBalance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
