<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Subject extends Model
{
    protected $fillable = [
        'code',
        'name',
        'subject_group_id',
        'learning_area',
        'level',
        'is_active',
        'is_optional',
        'meta'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_optional' => 'boolean',
        'meta' => 'array',
    ];
    
    public function group()
    {
        return $this->belongsTo(SubjectGroup::class, 'subject_group_id');
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_subjects')
            ->withPivot(['stream_id', 'staff_id', 'academic_year_id', 'term_id', 'is_compulsory'])
            ->withTimestamps();
    }

    public function classroomSubjects()
    {
        return $this->hasMany(ClassroomSubject::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'subject_teacher', 'subject_id', 'teacher_id');
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOptional(Builder $query)
    {
        return $query->where('is_optional', true);
    }

    public function scopeMandatory(Builder $query)
    {
        return $query->where('is_optional', false);
    }

    public function scopeForLevel(Builder $query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeInGroup(Builder $query, $groupId)
    {
        return $query->where('subject_group_id', $groupId);
    }
}
