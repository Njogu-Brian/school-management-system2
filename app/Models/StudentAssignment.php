<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'morning_trip_id', 'evening_trip_id', 'morning_drop_off_point_id', 'evening_drop_off_point_id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function morningTrip()
    {
        return $this->belongsTo(Trip::class, 'morning_trip_id');
    }

    public function eveningTrip()
    {
        return $this->belongsTo(Trip::class, 'evening_trip_id');
    }

    public function morningDropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class, 'morning_drop_off_point_id');
    }

    public function eveningDropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class, 'evening_drop_off_point_id');
    }
}
