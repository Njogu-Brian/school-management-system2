<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledFeeCommunication extends Model
{
    protected $fillable = [
        'target',
        'student_id',
        'selected_student_ids',
        'classroom_ids',
        'exclude_staff',
        'exclude_student_ids',
        'filter_type',
        'balance_min',
        'balance_max',
        'balance_percent_min',
        'balance_percent_max',
        'channels',
        'template_id',
        'custom_message',
        'send_at',
        'recurrence_type',
        'recurrence_times',
        'recurrence_week_days',
        'recurrence_start_at',
        'recurrence_end_at',
        'recurrence_next_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'selected_student_ids' => 'array',
        'classroom_ids' => 'array',
        'exclude_staff' => 'boolean',
        'exclude_student_ids' => 'array',
        'channels' => 'array',
        'recurrence_times' => 'array',
        'recurrence_week_days' => 'array',
        'send_at' => 'datetime',
        'recurrence_start_at' => 'datetime',
        'recurrence_end_at' => 'datetime',
        'recurrence_next_at' => 'datetime',
        'balance_min' => 'decimal:2',
        'balance_max' => 'decimal:2',
        'balance_percent_min' => 'decimal:2',
        'balance_percent_max' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function template()
    {
        return $this->belongsTo(CommunicationTemplate::class, 'template_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'active']);
    }

    public function scopeDue($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($q1) {
                $q1->where('recurrence_type', 'once')
                    ->where('send_at', '<=', now());
            })->orWhere(function ($q2) {
                $q2->whereIn('recurrence_type', ['daily', 'weekly', 'times_per_day'])
                    ->where('recurrence_next_at', '<=', now())
                    ->where(function ($q3) {
                        $q3->whereNull('recurrence_end_at')
                            ->orWhere('recurrence_end_at', '>=', now());
                    });
            });
        });
    }

    public function isRecurring(): bool
    {
        return in_array($this->recurrence_type ?? 'once', ['daily', 'weekly', 'times_per_day']);
    }

    public function getRecurrenceDescriptionAttribute(): string
    {
        $type = $this->recurrence_type ?? 'once';
        if ($type === 'once') {
            return 'Once';
        }
        $times = $this->recurrence_times ?? ['09:00'];
        $timesStr = implode(', ', array_map(function ($t) {
            try {
                return \Carbon\Carbon::parse($t)->format('g:i A');
            } catch (\Throwable $e) {
                return $t;
            }
        }, $times));
        return match ($type) {
            'daily' => 'Daily at ' . $timesStr,
            'weekly' => 'Weekly (' . $this->weekDaysLabel() . ') at ' . $timesStr,
            'times_per_day' => count($times) . '×/day at ' . $timesStr,
            default => 'Once',
        };
    }

    protected function weekDaysLabel(): string
    {
        $days = $this->recurrence_week_days ?? [1];
        $names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return implode(', ', array_filter(array_map(fn ($d) => $names[$d] ?? null, $days)));
    }
}
