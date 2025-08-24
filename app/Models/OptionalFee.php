<?php

// app/Models/OptionalFee.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionalFee extends Model
{
    protected $fillable = [
        'student_id', 'votehead_id', 'term', 'year', 'amount', 'is_active'
    ];

    public function student() {
        return $this->belongsTo(Student::class);
    }

    public function votehead() {
        return $this->belongsTo(Votehead::class);
    }
}
