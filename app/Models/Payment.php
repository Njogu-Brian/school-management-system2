<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['student_id', 'invoice_id', 'amount', 'payment_method', 'reference', 'payment_date', 'reversed'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}
