<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ReportCardSkill extends Model
{
    protected $fillable = [
        'name',           // e.g. "Punctuality", "Teamwork"
        'description',
        'classroom_id',   // null = global / all classes (optional if you prefer pivot)
        'is_active',
    ];

    public function classroom()
    {
        return $this->belongsTo(\App\Models\Academics\Classroom::class);
    }
}
