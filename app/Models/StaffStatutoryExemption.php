<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffStatutoryExemption extends Model
{
    protected $fillable = [
        'staff_id',
        'deduction_code',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

