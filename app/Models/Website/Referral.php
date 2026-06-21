<?php

namespace App\Models\Website;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referrer_name',
        'referrer_phone',
        'referrer_email',
        'referred_name',
        'referred_phone',
        'referred_email',
        'admitted_student_id',
        'status',
        'reward_notes',
    ];

    public function admittedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'admitted_student_id');
    }
}
