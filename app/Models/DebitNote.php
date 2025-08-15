<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebitNote extends Model
{
    protected $fillable = ['invoice_id', 'amount', 'reason', 'issued_at'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
