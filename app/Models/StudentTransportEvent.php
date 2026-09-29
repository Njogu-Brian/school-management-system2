<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransportEvent extends Model
{
    protected $fillable = [
        'student_id',
        'trip_run_id',
        'trip_id',
        'kind',
        'latitude',
        'longitude',
        'recorded_at',
        'marked_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tripRun(): BelongsTo
    {
        return $this->belongsTo(TripRun::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
