<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\Academics\Subject;

class Attendance extends Model
{
    protected $table = 'attendance';
    
    protected $fillable = [
        'student_id', 'date', 'status', 'reason',
        'reason_code_id',
        'is_excused', 'is_medical_leave', 'excuse_notes',
        'excuse_document_path', 'subject_id', 'period_number',
        'period_name', 'marked_by', 'marked_at', 'consecutive_absence_count'
    ];

    protected $casts = [
        'date' => 'date',
        'is_excused' => 'boolean',
        'is_medical_leave' => 'boolean',
        'marked_at' => 'datetime',
        'consecutive_absence_count' => 'integer',
        'period_number' => 'integer',
    ];

    const STATUS_PRESENT = 'present';
    const STATUS_ABSENT  = 'absent';
    const STATUS_LATE    = 'late';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function reasonCode()
    {
        return $this->belongsTo(AttendanceReasonCode::class, 'reason_code_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'marked_by');
    }

    // Helpers
    public function isPresent(): bool { return $this->status === self::STATUS_PRESENT; }
    public function isAbsent(): bool  { return $this->status === self::STATUS_ABSENT; }
    public function isLate(): bool    { return $this->status === self::STATUS_LATE; }
    public function isExcused(): bool { return $this->is_excused; }
    public function isMedicalLeave(): bool { return $this->is_medical_leave; }
    
}
