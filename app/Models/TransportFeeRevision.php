<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportFeeRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'transport_fee_id',
        'changed_by',
        'source',
        'old_amount',
        'new_amount',
        'old_drop_off_point_id',
        'new_drop_off_point_id',
        'old_drop_off_point_name',
        'new_drop_off_point_name',
        'note',
    ];

    protected $casts = [
        'old_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
    ];

    public function transportFee()
    {
        return $this->belongsTo(TransportFee::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

