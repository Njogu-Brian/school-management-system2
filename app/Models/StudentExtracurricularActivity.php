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
        'votehead_id',
        'fee_amount',
        'auto_bill',
        'billing_term',
        'billing_year',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'achievement_date' => 'date',
        'is_active' => 'boolean',
        'auto_bill' => 'boolean',
        'community_service_hours' => 'integer',
        'fee_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(\App\Models\User::class, 'supervisor_id');
    }

    public function votehead()
    {
        return $this->belongsTo(\App\Models\Votehead::class);
    }

    public function optionalFee()
    {
        return $this->hasOne(\App\Models\OptionalFee::class, 'student_id', 'student_id')
            ->where('votehead_id', $this->votehead_id)
            ->where('term', $this->billing_term)
            ->where('year', $this->billing_year);
    }
}
