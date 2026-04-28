<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassTeacherAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'classroom_id',
        'stream_id',
        'staff_id',
    ];
}

