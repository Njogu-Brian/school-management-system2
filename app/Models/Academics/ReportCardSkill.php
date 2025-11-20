<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ReportCardSkill extends Model
{
    protected $fillable = [
        'report_card_id',
        'skill_name',
        'name',
        'description',
        'classroom_id',
        'rating',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (ReportCardSkill $skill) {
            $label = $skill->name ?: $skill->skill_name;
            $skill->name = $label;
            $skill->skill_name = $label;

            if (is_null($skill->is_active)) {
                $skill->is_active = true;
            }
        });
    }

    public function classroom()
    {
        return $this->belongsTo(\App\Models\Academics\Classroom::class);
    }

    public function scopeActive($query)
    {
        $table = $query->getModel()->getTable();

        if (Schema::hasColumn($table, 'is_active')) {
            return $query->where("$table.is_active", true);
        }

        return $query;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->skill_name ?? '';
    }
}
