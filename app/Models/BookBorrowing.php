<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class BookBorrowing extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_copy_id',
        'library_card_id',
        'student_id',
        'borrowed_date',
        'due_date',
        'returned_date',
        'status',
        'fine_amount',
        'fine_paid',
        'notes',
        'borrowed_by',
        'returned_by',
    ];

    protected $casts = [
        'borrowed_date' => 'date',
        'due_date' => 'date',
        'returned_date' => 'date',
        'fine_amount' => 'decimal:2',
        'fine_paid' => 'boolean',
    ];

    /**
     * Get the book copy
     */
    public function bookCopy(): BelongsTo
    {
        return $this->belongsTo(BookCopy::class);
    }

    /**
     * Get the library card
     */
    public function libraryCard(): BelongsTo
    {
        return $this->belongsTo(LibraryCard::class);
    }

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Check if borrowing is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === 'borrowed' && $this->due_date->isPast();
    }

    /**
     * Calculate fine amount
     */
    public function calculateFine(float $dailyFineRate = 10.00): float
    {
        if (!$this->isOverdue() || $this->fine_paid) {
            return 0;
        }

        $daysOverdue = max(0, Carbon::now()->diffInDays($this->due_date));
        return $daysOverdue * $dailyFineRate;
    }
}

