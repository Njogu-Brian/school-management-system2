<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'subject_group_id','code','name','learning_area','level','is_active','meta'
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function group() { return $this->belongsTo(SubjectGroup::class); }
    public function classroomSubjects() { return $this->hasMany(ClassroomSubject::class); }
    public function exams() { return $this->hasMany(Exam::class); }
}
