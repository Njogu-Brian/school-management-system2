<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class CampusSeniorTeacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'senior_teacher_id',
    ];

    public function seniorTeacher()
    {
        return $this->belongsTo(User::class, 'senior_teacher_id');
    }
}
