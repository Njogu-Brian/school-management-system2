<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookCopy extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'copy_number',
        'barcode',
        'status',
        'condition',
        'purchase_date',
        'purchase_price',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    /**
     * Get the book this copy belongs to
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get borrowings for this copy
     */
    public function borrowings(): HasMany
    {
        return $this->hasMany(BookBorrowing::class);
    }

    /**
     * Get current borrowing (if any)
     */
    public function currentBorrowing()
    {
        return $this->borrowings()->where('status', 'borrowed')->first();
    }

    /**
     * Check if copy is available
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}

