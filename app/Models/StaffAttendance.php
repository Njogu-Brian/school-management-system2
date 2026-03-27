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
        'check_in_latitude',
        'check_in_longitude',
        'check_in_distance_meters',
        'check_in_accuracy_meters',
        'check_out_time',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_distance_meters',
        'check_out_accuracy_meters',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'check_in_latitude' => 'float',
        'check_in_longitude' => 'float',
        'check_in_distance_meters' => 'float',
        'check_in_accuracy_meters' => 'float',
        'check_out_latitude' => 'float',
        'check_out_longitude' => 'float',
        'check_out_distance_meters' => 'float',
        'check_out_accuracy_meters' => 'float',
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
