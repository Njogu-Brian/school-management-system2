<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\User;

class StudentDiary extends Model
{
    protected $fillable = ['student_id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function entries()
    {
        return $this->hasMany(DiaryEntry::class)->orderBy('created_at', 'asc');
    }

    public function latestEntry()
    {
        return $this->hasOne(DiaryEntry::class)->latestOfMany();
    }

    /**
     * Get unread entries count for a specific user
     */
    public function unreadCountForUser($userId)
    {
        return $this->entries()
            ->where('author_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }
}

