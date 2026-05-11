<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityFeeAttendance extends Model
{
    protected $table = 'activity_fee_attendances';

    protected $fillable = [
        'votehead_id',
        'student_id',
        'attendance_date',
        'notes',
        'marked_by',
        'marked_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'marked_at' => 'datetime',
    ];

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
