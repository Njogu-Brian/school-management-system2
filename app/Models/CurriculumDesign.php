<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Academics\Subject;

class CurriculumDesign extends Model
{
    protected $fillable = [
        'title',
        'subject_id',
        'class_level',
        'uploaded_by',
        'file_path',
        'pages',
        'status',
        'checksum',
        'metadata',
        'error_notes',
    ];

    protected $casts = [
        'pages' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the subject this curriculum design belongs to
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    /**
     * Get the user who uploaded this curriculum design
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get all pages for this curriculum design
     */
    public function pages(): HasMany
    {
        return $this->hasMany(CurriculumPage::class, 'curriculum_design_id');
    }

    /**
     * Get all learning areas extracted from this curriculum design
     */
    public function learningAreas(): HasMany
    {
        return $this->hasMany(\App\Models\Academics\LearningArea::class, 'curriculum_design_id');
    }

    /**
     * Get all strands extracted from this curriculum design
     */
    public function strands(): HasMany
    {
        return $this->hasMany(\App\Models\Academics\CBCStrand::class, 'curriculum_design_id');
    }

    /**
     * Get all embeddings for this curriculum design
     */
    public function embeddings(): HasMany
    {
        return $this->hasMany(CurriculumEmbedding::class, 'curriculum_design_id');
    }

    /**
     * Get all audit logs for this curriculum design
     */
    public function audits(): HasMany
    {
        return $this->hasMany(CurriculumExtractionAudit::class, 'curriculum_design_id');
    }

    /**
     * Check if the curriculum design is processed
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if the curriculum design is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the curriculum design failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
