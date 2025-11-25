<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedDocument extends Model
{
    protected $fillable = [
        'template_id',
        'student_id',
        'staff_id',
        'document_type',
        'pdf_path',
        'data',
        'filename',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'generated_at' => 'datetime',
    ];

    /**
     * Get the template used to generate this document
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    /**
     * Get the student this document is for (if applicable)
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the staff member this document is for (if applicable)
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the user who generated this document
     */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Check if document PDF exists
     */
    public function hasPdf(): bool
    {
        return $this->pdf_path && file_exists(storage_path('app/public/' . $this->pdf_path));
    }

    /**
     * Get full path to PDF
     */
    public function getPdfPathAttribute($value): ?string
    {
        if (!$value) {
            return null;
        }

        return storage_path('app/public/' . $value);
    }
}

