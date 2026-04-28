<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeniorTeacherClassroomAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'senior_teacher_id',
        'classroom_id',
    ];
}

