<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudentConcern extends Model
{
    public const CATEGORIES = [
        'financial',
        'academic',
        'teacher',
        'transport',
        'meals',
        'administration',
    ];

    protected $fillable = [
        'student_id',
        'category',
        'description',
        'status',
        'raised_by_user_id',
        'created_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function raisedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function concernedStaff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'student_concern_staff')
            ->withTimestamps();
    }
}
