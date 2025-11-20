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
     * Primary classroom (the main classroom this stream belongs to)
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Additional classrooms this stream is assigned to (via pivot table)
     */
    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_stream');
    }

    /**
     * Get all classrooms this stream is assigned to (primary + additional)
     */
    public function allClassrooms()
    {
        $primary = $this->classroom;
        $additional = $this->classrooms;
        
        $all = collect();
        if ($primary) {
            $all->push($primary);
        }
        $all = $all->merge($additional)->unique('id');
        
        return $all;
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
