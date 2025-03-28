<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Stream;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;

class Classroom extends Model
{
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
}
