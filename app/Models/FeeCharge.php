<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeCharge extends Model
{
    protected $fillable = ['fee_structure_id', 'votehead_id', 'term', 'amount'];

    public function votehead()
    {
        return $this->belongsTo(Votehead::class);
    }

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class);
    }
}
