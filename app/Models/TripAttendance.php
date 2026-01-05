<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripAttendance extends Model
{
    protected $fillable = [
        'trip_id',
        'student_id',
        'attendance_date',
        'status',
        'boarded_at',
        'notes',
        'marked_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'boarded_at' => 'datetime:H:i',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
