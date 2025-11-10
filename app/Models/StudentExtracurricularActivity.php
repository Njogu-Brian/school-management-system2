<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentExtracurricularActivity extends Model
{
    protected $fillable = [
        'student_id',
        'activity_type',
        'activity_name',
        'description',
        'start_date',
        'end_date',
        'position_role',
        'team_name',
        'competition_name',
        'competition_level',
        'award_achievement',
        'achievement_description',
        'achievement_date',
        'community_service_hours',
        'notes',
        'is_active',
        'supervisor_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'achievement_date' => 'date',
        'is_active' => 'boolean',
        'community_service_hours' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\User::class, 'supervisor_id');
    }
}
