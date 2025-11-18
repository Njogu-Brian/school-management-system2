<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumExtractionAudit extends Model
{
    protected $fillable = [
        'curriculum_design_id',
        'user_id',
        'action',
        'notes',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    /**
     * Get the curriculum design this audit belongs to
     */
    public function curriculumDesign(): BelongsTo
    {
        return $this->belongsTo(CurriculumDesign::class, 'curriculum_design_id');
    }

    /**
     * Get the user who performed this action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
