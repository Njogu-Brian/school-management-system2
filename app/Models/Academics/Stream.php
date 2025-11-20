<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Stream extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'classroom_id'];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_stream');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'stream_teacher', 'stream_id', 'teacher_id')
            ->withPivot('classroom_id')
            ->withTimestamps();
    }

    /**
     * Get teachers for this stream in a specific classroom
     */
    public function teachersForClassroom($classroomId)
    {
        return $this->belongsToMany(User::class, 'stream_teacher', 'stream_id', 'teacher_id')
            ->wherePivot('classroom_id', $classroomId)
            ->withPivot('classroom_id')
            ->withTimestamps();
    }
}
