<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'votehead_id', 'amount',
        'status', 'effective_date', 'source',
    ];
        protected $casts = [
        'effective_date' => 'date', 
    ];
    public function invoice()  { return $this->belongsTo(Invoice::class); }
    public function votehead() { return $this->belongsTo(Votehead::class); }
}
