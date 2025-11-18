<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumEmbedding extends Model
{
    protected $fillable = [
        'curriculum_design_id',
        'source_type',
        'source_id',
        'text_snippet',
        'vector_store_id',
        'metadata',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the curriculum design this embedding belongs to
     */
    public function curriculumDesign(): BelongsTo
    {
        return $this->belongsTo(CurriculumDesign::class, 'curriculum_design_id');
    }

    /**
     * Get the source entity based on source_type and source_id
     */
    public function getSourceAttribute()
    {
        if (!$this->source_id) {
            return null;
        }

        return match ($this->source_type) {
            'page' => CurriculumPage::find($this->source_id),
            'competency' => \App\Models\Academics\Competency::find($this->source_id),
            'strand' => \App\Models\Academics\CBCStrand::find($this->source_id),
            'substrand' => \App\Models\Academics\CBCSubstrand::find($this->source_id),
            'rubric' => AssessmentRubric::find($this->source_id),
            'experience' => SuggestedExperience::find($this->source_id),
            default => null,
        };
    }
}
