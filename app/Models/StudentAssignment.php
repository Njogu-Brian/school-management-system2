<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\Trip;
use App\Models\DropOffPoint;

class StudentAssignment extends Model
{
    protected $fillable = ['student_id', 'trip_id', 'drop_off_point_id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function dropOffPoint()
    {
        return $this->belongsTo(DropOffPoint::class);
    }
}
