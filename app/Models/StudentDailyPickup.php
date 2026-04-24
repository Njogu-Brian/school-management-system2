<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDailyPickup extends Model
{
    protected $fillable = [
        'student_id',
        'date',
        'recorded_by_user_id',
        'picked_up_by',
        'direction',
        'skip_evening_trip',
        'notes',
        'transport_special_assignment_id',
    ];

    protected $casts = [
        'date' => 'date',
        'skip_evening_trip' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function specialAssignment()
    {
        return $this->belongsTo(TransportSpecialAssignment::class, 'transport_special_assignment_id');
    }
}
