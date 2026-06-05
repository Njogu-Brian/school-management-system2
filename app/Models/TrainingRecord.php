<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingRecord extends Model
{
    protected $fillable = [
        'staff_id',
        'training_course_id',
        'training_name',
        'provider',
        'location',
        'start_date',
        'end_date',
        'duration_hours',
        'training_type',
        'description',
        'objectives',
        'outcomes',
        'certificate_number',
        'certificate_file',
        'cost',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
