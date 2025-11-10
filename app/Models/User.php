<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function subjects()
    {
        return $this->belongsToMany(\App\Models\Academics\Subject::class, 'subject_teacher', 'teacher_id', 'subject_id');
    }

    public function classrooms()
    {
        return $this->belongsToMany(\App\Models\Academics\Classroom::class, 'classroom_teacher', 'teacher_id', 'classroom_id');
    }

    public function streams()
    {
        return $this->belongsToMany(\App\Models\Academics\Stream::class, 'stream_teacher', 'teacher_id', 'stream_id');
    }

    /**
     * Check if teacher is assigned to a classroom
     */
    public function isAssignedToClassroom($classroomId): bool
    {
        return $this->classrooms()->where('classrooms.id', $classroomId)->exists();
    }

    /**
     * Check if teacher is assigned to a stream
     */
    public function isAssignedToStream($streamId): bool
    {
        return $this->streams()->where('streams.id', $streamId)->exists();
    }

    /**
     * Get all classroom IDs assigned to this teacher
     */
    public function getAssignedClassroomIds(): array
    {
        return $this->classrooms()->pluck('classrooms.id')->toArray();
    }

    /**
     * Get all stream IDs assigned to this teacher
     */
    public function getAssignedStreamIds(): array
    {
        return $this->streams()->pluck('streams.id')->toArray();
    }
}
