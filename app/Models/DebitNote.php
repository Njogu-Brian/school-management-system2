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
            if (!$debitNote->issued_by && auth()->check()) {
                $debitNote->issued_by = auth()->id();
            }
            if (!$debitNote->issued_at) {
                $debitNote->issued_at = now();
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
