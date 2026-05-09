<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerPosting extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_type',
        'source_id',
        'account_code',
        'dr_cr',
        'amount',
        'posting_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'posting_date' => 'date',
    ];
}
