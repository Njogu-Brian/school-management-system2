<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDisciplinaryRecord extends Model
{
    protected $fillable = [
        'student_id',
        'incident_date',
        'incident_time',
        'incident_type',
        'severity',
        'description',
        'witnesses',
        'action_taken',
        'action_details',
        'action_date',
        'improvement_plan',
        'parent_notified',
        'parent_notification_date',
        'follow_up_notes',
        'follow_up_date',
        'resolved',
        'resolved_date',
        'reported_by',
        'action_taken_by',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'action_date' => 'date',
        'parent_notification_date' => 'date',
        'follow_up_date' => 'date',
        'resolved_date' => 'date',
        'parent_notified' => 'boolean',
        'resolved' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function reportedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'reported_by');
    }

    public function actionTakenBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'action_taken_by');
    }
}
