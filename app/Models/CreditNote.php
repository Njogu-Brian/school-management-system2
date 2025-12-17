<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\{Invoice, InvoiceItem, User};

class CreditNote extends Model
{
    protected $fillable = [
        'invoice_id',
        'invoice_item_id',
        'credit_note_number',
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
        
        static::creating(function ($creditNote) {
            if (!$creditNote->credit_note_number) {
                $creditNote->credit_note_number = \App\Services\DocumentNumberService::generate('credit_note', 'CN');
            }
            if (!$creditNote->hashed_id) {
                $creditNote->hashed_id = self::generateHashedId();
            }
            if (!$creditNote->issued_by) {
                $creditNote->issued_by = auth()->id();
            }
            if (!$creditNote->issued_at) {
                $creditNote->issued_at = now();
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
