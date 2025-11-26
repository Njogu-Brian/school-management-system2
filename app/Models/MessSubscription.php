<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class MessSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'hostel_id',
        'meal_plan',
        'start_date',
        'end_date',
        'status',
        'monthly_fee',
        'custom_meals',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_fee' => 'decimal:2',
        'custom_meals' => 'array',
    ];

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the hostel
     */
    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' 
            && $this->start_date->isPast() 
            && ($this->end_date === null || $this->end_date->isFuture());
    }
}

