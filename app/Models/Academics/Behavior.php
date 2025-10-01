<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class Behavior extends Model
{
    protected $fillable = [
        'student_id',     // FK -> students.id
        'category',       // e.g. Punctuality / Homework / Bullying
        'description',    // free text
        'severity',       // minor | moderate | major
        'recorded_by',    // FK -> users.id (who recorded)
    ];

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class);
    }

    public function recorder()
    {
        return $this->belongsTo(\App\Models\User::class, 'recorded_by');
    }
}
