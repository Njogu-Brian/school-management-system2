<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AcademicYear;

class StaffLeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'leave_type_id',
        'academic_year_id',
        'entitlement_days',
        'used_days',
        'remaining_days',
        'carried_forward',
    ];

    protected $casts = [
        'entitlement_days' => 'integer',
        'used_days' => 'integer',
        'remaining_days' => 'integer',
        'carried_forward' => 'integer',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Calculate remaining days
     */
    public function calculateRemaining()
    {
        $this->remaining_days = ($this->entitlement_days + $this->carried_forward) - $this->used_days;
        $this->save();
        return $this->remaining_days;
    }
}
