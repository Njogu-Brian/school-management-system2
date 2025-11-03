<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $fillable = [
        'journal_number','student_id','votehead_id','year','term',
        'type','amount','effective_date','reason','invoice_id','invoice_item_id','meta'
    ];

    protected $casts = ['meta' => 'array', 'effective_date' => 'date'];

    public function student()     { return $this->belongsTo(Student::class); }
    public function votehead()    { return $this->belongsTo(Votehead::class); }
    public function invoice()     { return $this->belongsTo(Invoice::class); }
    public function invoiceItem() { return $this->belongsTo(InvoiceItem::class); }
}
