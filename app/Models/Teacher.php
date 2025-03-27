<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Teacher extends Authenticatable
{
    protected $guard = 'teacher';
    protected $fillable = ['name', 'email', 'password', 'class'];

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_teacher');
    }

}