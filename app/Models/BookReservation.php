<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BookReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'student_id',
        'library_card_id',
        'reserved_date',
        'expiry_date',
        'status',
        'notified_at',
        'notes',
    ];

    protected $casts = [
        'reserved_date' => 'date',
        'expiry_date' => 'date',
        'notified_at' => 'datetime',
    ];

    /**
     * Get the book
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the library card
     */
    public function libraryCard(): BelongsTo
    {
        return $this->belongsTo(LibraryCard::class);
    }

    /**
     * Check if reservation is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date->isPast() && $this->status === 'pending';
    }
}

