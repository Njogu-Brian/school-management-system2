<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Stream extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'classroom_id'];

    /**
     * Each stream belongs to exactly one classroom
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get teachers assigned to this stream
     * Since each stream belongs to one classroom, classroom_id in pivot should match stream's classroom_id
     */
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'stream_teacher', 'stream_id', 'teacher_id')
            ->withPivot('classroom_id')
            ->withTimestamps();
    }

    /**
     * Get teachers for this stream filtered by the stream's classroom_id
     * Use this when you need only teachers for the current classroom
     */
    public function teachersForClassroom()
    {
        if (!$this->classroom_id) {
            return collect();
        }
        
        return $this->belongsToMany(User::class, 'stream_teacher', 'stream_id', 'teacher_id')
            ->wherePivot('classroom_id', $this->classroom_id)
            ->withPivot('classroom_id')
            ->withTimestamps();
    }
}
