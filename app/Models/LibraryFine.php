<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryFine extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrowing_id',
        'student_id',
        'amount',
        'reason',
        'status',
        'paid_at',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    /**
     * Get the borrowing this fine is for
     */
    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(BookBorrowing::class);
    }

    /**
     * Get the student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Check if fine is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}

