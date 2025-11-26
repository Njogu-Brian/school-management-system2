<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'isbn',
        'title',
        'author',
        'publisher',
        'publication_year',
        'category',
        'language',
        'total_copies',
        'available_copies',
        'location',
        'description',
        'cover_image',
    ];

    protected $casts = [
        'publication_year' => 'integer',
        'total_copies' => 'integer',
        'available_copies' => 'integer',
    ];

    /**
     * Get all copies of this book
     */
    public function copies(): HasMany
    {
        return $this->hasMany(BookCopy::class);
    }

    /**
     * Get available copies
     */
    public function availableCopies(): HasMany
    {
        return $this->hasMany(BookCopy::class)->where('status', 'available');
    }

    /**
     * Get reservations for this book
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(BookReservation::class);
    }

    /**
     * Check if book is available
     */
    public function isAvailable(): bool
    {
        return $this->available_copies > 0;
    }

    /**
     * Update available copies count
     */
    public function updateAvailableCount(): void
    {
        $this->available_copies = $this->copies()->where('status', 'available')->count();
        $this->save();
    }
}

