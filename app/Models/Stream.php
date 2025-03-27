<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    protected $fillable = ['name'];

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_stream');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'stream_teacher', 'stream_id', 'teacher_id');
    }
}
