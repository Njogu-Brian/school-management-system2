<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
class Attendance extends Model
{
    protected $table = 'attendance';
    protected $fillable = ['student_id', 'date', 'status', 'reason'];

    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT  = 'absent';
    const STATUS_LATE    = 'late';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Helpers
    public function isPresent(): bool { return $this->status === self::STATUS_PRESENT; }
    public function isAbsent(): bool  { return $this->status === self::STATUS_ABSENT; }
    public function isLate(): bool    { return $this->status === self::STATUS_LATE; }
}
