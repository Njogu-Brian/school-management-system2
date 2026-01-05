<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportSpecialAssignment extends Model
{
    protected $fillable = [
        'student_id',
        'vehicle_id',
        'trip_id',
        'drop_off_point_id',
        'assignment_type',
        'transport_mode',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function dropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if assignment is currently active
     */
    public function isActive(): bool
    {
        $today = now()->toDateString();
        return $this->status === 'active' 
            && $this->start_date <= $today 
            && ($this->end_date === null || $this->end_date >= $today);
    }
}
