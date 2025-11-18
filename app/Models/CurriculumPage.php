<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumPage extends Model
{
    protected $fillable = [
        'curriculum_design_id',
        'page_number',
        'text',
        'ocr_confidence',
        'raw_html',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'ocr_confidence' => 'decimal:2',
    ];

    /**
     * Get the curriculum design this page belongs to
     */
    public function curriculumDesign(): BelongsTo
    {
        return $this->belongsTo(CurriculumDesign::class, 'curriculum_design_id');
    }

    /**
     * Check if this page was processed with OCR
     */
    public function wasOcrProcessed(): bool
    {
        return $this->ocr_confidence !== null;
    }
}
