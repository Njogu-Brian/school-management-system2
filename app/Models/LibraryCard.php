<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class LibraryCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'card_number',
        'issued_date',
        'expiry_date',
        'status',
        'max_borrow_limit',
        'current_borrow_count',
        'notes',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'max_borrow_limit' => 'integer',
        'current_borrow_count' => 'integer',
    ];

    /**
     * Get the student this card belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get borrowings for this card
     */
    public function borrowings(): HasMany
    {
        return $this->hasMany(BookBorrowing::class);
    }

    /**
     * Get active borrowings
     */
    public function activeBorrowings(): HasMany
    {
        return $this->hasMany(BookBorrowing::class)->where('status', 'borrowed');
    }

    /**
     * Check if card is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expiry_date->isFuture();
    }

    /**
     * Check if can borrow more books
     */
    public function canBorrow(): bool
    {
        return $this->isActive() && $this->current_borrow_count < $this->max_borrow_limit;
    }

    /**
     * Generate unique card number
     */
    public static function generateCardNumber(): string
    {
        do {
            $number = 'LIB-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('card_number', $number)->exists());

        return $number;
    }
}

