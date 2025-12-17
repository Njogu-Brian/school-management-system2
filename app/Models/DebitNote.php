<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\{Invoice, InvoiceItem, User};

class DebitNote extends Model
{
    protected $fillable = [
        'invoice_id',
        'invoice_item_id',
        'debit_note_number',
        'hashed_id',
        'amount',
        'reason',
        'notes',
        'issued_at',
        'issued_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($debitNote) {
            if (!$debitNote->debit_note_number) {
                try {
                    $debitNote->debit_note_number = \App\Services\DocumentNumberService::generateDebitNote();
                } catch (\Exception $e) {
                    // Fallback
                    $debitNote->debit_note_number = 'DN-' . date('Y') . '-' . str_pad((DebitNote::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
                }
            }
            if (!$debitNote->hashed_id) {
                $debitNote->hashed_id = self::generateHashedId();
            }
            if (!$debitNote->issued_by && auth()->check()) {
                $debitNote->issued_by = auth()->id();
            }
            if (!$debitNote->issued_at) {
                $debitNote->issued_at = now();
            }
        });
    }

    /**
     * Generate hashed ID for secure URL access
     */
    public static function generateHashedId(): string
    {
        do {
            $hash = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 0, 10);
        } while (self::where('hashed_id', $hash)->exists());
        
        return $hash;
    }

    /**
     * Get route key name - use ID for internal routes
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Resolve route binding - support both ID and hashed_id
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === 'hashed_id') {
            return $this->where('hashed_id', $value)->firstOrFail();
        }
        
        if (is_numeric($value)) {
            return $this->where('id', $value)->firstOrFail();
        }
        
        return $this->where('hashed_id', $value)->firstOrFail();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
