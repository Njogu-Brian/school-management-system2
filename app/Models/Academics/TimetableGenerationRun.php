<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class TimetableGenerationRun extends Model
{
    protected $fillable = [
        'academic_year_id',
        'term_id',
        'scope',
        'status',
        'settings',
        'summary',
        'created_by',
    ];

    protected $casts = [
        'settings' => 'array',
        'summary' => 'array',
    ];

    public function slots()
    {
        return $this->hasMany(TimetableGeneratedSlot::class, 'run_id');
    }
}

