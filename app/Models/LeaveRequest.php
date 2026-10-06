<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\User;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'days_requested',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'admin_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'days_requested' => 'integer',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Calculate working days between start and end date (excluding weekends)
     */
    public function calculateDays()
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        $days = 0;

        while ($start <= $end) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            if ($start->dayOfWeek != Carbon::SATURDAY && $start->dayOfWeek != Carbon::SUNDAY) {
                $days++;
            }
            $start->addDay();
        }

        return $days;
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Formatted time window for partial-day leave (display only; balance still uses days).
     */
    public function timeWindowLabel(): ?string
    {
        if (! $this->start_time && ! $this->end_time) {
            return null;
        }

        $start = $this->start_time ? Carbon::parse($this->start_time)->format('H:i') : null;
        $end = $this->end_time ? Carbon::parse($this->end_time)->format('H:i') : null;

        if ($start && $end) {
            $mins = Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time));
            $hours = round($mins / 60, 1);

            return "{$start}–{$end}".($hours > 0 ? " ({$hours} hrs)" : '');
        }

        return $start ?: $end;
    }
}
