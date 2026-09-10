<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\Student;

class StudentDiary extends Model
{
    public const CHANNEL_TEACHER_PARENT = 'teacher_parent';
    public const CHANNEL_ADMIN_PARENT = 'admin_parent';

    protected $fillable = ['student_id', 'channel'];

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

    public function unreadCountForUser($userId)
    {
        return $this->entries()
            ->where('author_id', '!=', $userId)
            ->where('is_read', false)
            ->count();
    }

    public static function normalizeChannel(?string $channel): string
    {
        $channel = $channel ?: self::CHANNEL_TEACHER_PARENT;
        if (! in_array($channel, [self::CHANNEL_TEACHER_PARENT, self::CHANNEL_ADMIN_PARENT], true)) {
            return self::CHANNEL_TEACHER_PARENT;
        }

        return $channel;
    }
}
