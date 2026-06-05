<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    protected $fillable = [
        'staff_id',
        'reviewer_id',
        'review_type',
        'review_period_start',
        'review_period_end',
        'review_date',
        'overall_rating',
        'category_ratings',
        'strengths',
        'areas_for_improvement',
        'achievements',
        'goals_met',
        'comments',
        'reviewer_comments',
        'status',
        'acknowledged_at',
        'created_by',
    ];

    protected $casts = [
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'review_date' => 'date',
        'overall_rating' => 'decimal:2',
        'category_ratings' => 'array',
        'acknowledged_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(Staff::class, 'reviewer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
