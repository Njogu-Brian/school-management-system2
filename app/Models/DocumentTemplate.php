<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'template_html',
        'placeholders',
        'settings',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all generated documents using this template
     */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class, 'template_id');
    }

    /**
     * Scope to get only active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}

