<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'hostel_id',
        'room_id',
        'bed_number',
        'allocation_date',
        'deallocation_date',
        'status',
        'notes',
        'allocated_by',
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'deallocation_date' => 'date',
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
     * Get the room
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }

    /**
     * Check if allocation is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}

