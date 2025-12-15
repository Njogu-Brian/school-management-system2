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
            if (!$creditNote->issued_by) {
                $creditNote->issued_by = auth()->id();
            }
            if (!$creditNote->issued_at) {
                $creditNote->issued_at = now();
            }
        });
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
