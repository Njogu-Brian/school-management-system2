<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\{Student, Votehead, AcademicYear, User};

class OptionalFee extends Model
{
    protected $fillable = [
        'student_id',
        'votehead_id',
        'term',
        'year', // Keep for backward compatibility
        'academic_year_id',
        'amount',
        'status', // billed|exempt
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'assigned_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function votehead(): BelongsTo
    {
        return $this->belongsTo(Votehead::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

