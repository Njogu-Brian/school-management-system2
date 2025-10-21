<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Student;   // ✅ import Student from App\Models
use App\Models\User;      // ✅ import User from App\Models

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function streams()
    {
        return $this->belongsToMany(Stream::class, 'classroom_stream');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'classroom_id');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'classroom_teacher', 'classroom_id', 'teacher_id');
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'classroom_subject');
    }
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

}
