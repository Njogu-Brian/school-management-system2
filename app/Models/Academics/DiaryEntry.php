<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Student;

class DiaryEntry extends Model
{
    protected $fillable = [
        'student_diary_id',
        'author_id',
        'author_type',
        'parent_entry_id',
        'content',
        'attachments',
        'is_read',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_read' => 'boolean',
    ];

    public function studentDiary()
    {
        return $this->belongsTo(StudentDiary::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parentEntry()
    {
        return $this->belongsTo(DiaryEntry::class, 'parent_entry_id');
    }

    public function replies()
    {
        return $this->hasMany(DiaryEntry::class, 'parent_entry_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the student this entry belongs to
     */
    public function student()
    {
        return $this->studentDiary->student;
    }

    /**
     * Get author name with role
     */
    public function getAuthorNameAttribute()
    {
        if ($this->author_type === 'parent') {
            $parent = $this->author->parent ?? $this->student()->parent;
            return $parent ? $parent->first_name . ' ' . $parent->last_name . ' (Parent)' : $this->author->name;
        } elseif ($this->author_type === 'teacher') {
            $staff = $this->author->staff;
            return $staff ? $staff->first_name . ' ' . $staff->last_name . ' (Teacher)' : $this->author->name;
        } else {
            return $this->author->name . ' (Admin)';
        }
    }
}

