<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_attendance';

    protected $fillable = [
        'staff_id',
        'date',
        'status',
        'check_in_time',
        'check_out_time',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function isPresent()
    {
        return $this->status === 'present';
    }

    public function isAbsent()
    {
        return $this->status === 'absent';
    }

    public function isLate()
    {
        return $this->status === 'late';
    }

    public function isHalfDay()
    {
        return $this->status === 'half_day';
    }
}
