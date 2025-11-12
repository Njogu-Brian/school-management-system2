<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class CBCPerformanceLevel extends Model
{
    protected $table = 'cbc_performance_levels';

    protected $fillable = [
        'code',
        'name',
        'min_percentage',
        'max_percentage',
        'description',
        'color_code',
        'display_order',
        'is_active'
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
        'max_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function examMarks()
    {
        return $this->hasMany(\App\Models\Academics\ExamMark::class, 'performance_level_id');
    }

    public function reportCards()
    {
        return $this->hasMany(\App\Models\Academics\ReportCard::class, 'overall_performance_level_id');
    }

    public function portfolioAssessments()
    {
        return $this->hasMany(PortfolioAssessment::class, 'performance_level_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('min_percentage', 'desc');
    }

    // Helper method
    public static function getByScore($score)
    {
        return static::where('min_percentage', '<=', $score)
            ->where('max_percentage', '>=', $score)
            ->where('is_active', true)
            ->first();
    }
}
