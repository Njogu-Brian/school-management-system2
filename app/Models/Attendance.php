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
        'arrival_time', 'departure_time', 'reason_code_id',
        'is_excused', 'is_medical_leave', 'excuse_notes',
        'excuse_document_path', 'subject_id', 'period_number',
        'period_name', 'marked_by', 'marked_at', 'consecutive_absence_count'
    ];

    protected $casts = [
        'date' => 'date',
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
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
    
    /**
     * Check if this is a late arrival
     */
    public function isLateArrival(): bool
    {
        if (!$this->arrival_time || $this->status !== self::STATUS_PRESENT) {
            return false;
        }
        
        // Assuming school starts at 8:00 AM - can be made configurable
        $schoolStartTime = config('attendance.school_start_time', '08:00:00');
        return $this->arrival_time->format('H:i:s') > $schoolStartTime;
    }
    
    /**
     * Check if this is an early departure
     */
    public function isEarlyDeparture(): bool
    {
        if (!$this->departure_time || $this->status !== self::STATUS_PRESENT) {
            return false;
        }
        
        // Assuming school ends at 3:00 PM - can be made configurable
        $schoolEndTime = config('attendance.school_end_time', '15:00:00');
        return $this->departure_time->format('H:i:s') < $schoolEndTime;
    }
}
