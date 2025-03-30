<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Transport;
use App\Models\Attendance;
use App\Models\ParentInfo;
use App\Models\StudentCategory;
use App\Models\Stream;
use App\Models\Classroom;
use App\Models\StudentAssignment;
use App\Models\DropOffPoint;
use App\Models\Trip;
use App\Models\Vehicle;

class Student extends Model
{
    protected $fillable = [
        'admission_number',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'dob',
        'parent_id',
        'classroom_id',
        'stream_id',
        'category_id',
        'nemis_number',
        'knec_assessment_number',
        'archive',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceForToday()
    {
        return $this->hasOne(Attendance::class)->whereDate('date', today());
    }

    public function route()
    {
        return $this->belongsTo(Transport::class, 'route_id');
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }

    public function category()
    {
        return $this->belongsTo(StudentCategory::class, 'category_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->middle_name} {$this->last_name}";
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
    public function siblings()
    {
        return $this->belongsToMany(Student::class, 'student_siblings', 'student_id', 'sibling_id');
    }
    public function assignments()
    {
        return $this->hasMany(StudentAssignment::class);
    }

}
