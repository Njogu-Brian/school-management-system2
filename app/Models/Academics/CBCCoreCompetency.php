<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class CBCCoreCompetency extends Model
{
    protected $table = 'cbc_core_competencies';

    protected $fillable = [
        'code',
        'name',
        'description',
        'learning_area',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    // Helper to get all active competencies
    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }
}
