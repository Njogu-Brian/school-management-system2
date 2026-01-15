<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SwimmingAttendance extends Model
{
    protected $table = 'swimming_attendance';

    protected $fillable = [
        'student_id',
        'classroom_id',
        'attendance_date',
        'payment_status',
        'session_cost',
        'termly_fee_covered',
        'notes',
        'marked_by',
        'marked_at',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'session_cost' => 'decimal:2',
        'termly_fee_covered' => 'boolean',
        'marked_at' => 'datetime',
    ];

    const STATUS_PAID = 'paid';
    const STATUS_UNPAID = 'unpaid';

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === self::STATUS_UNPAID;
    }
}
