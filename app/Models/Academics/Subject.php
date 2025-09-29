<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Subject extends Model
{
    protected $fillable = [
        'code','name','subject_group_id','learning_area'
    ];
    
    public function group()
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_subject');
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'subject_teacher', 'subject_id', 'teacher_id');
    }
}
