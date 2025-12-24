<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchiveAudit extends Model
{
    protected $fillable = [
        'student_id',
        'actor_id',
        'action',
        'reason',
        'counts',
    ];

    protected $casts = [
        'counts' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}

