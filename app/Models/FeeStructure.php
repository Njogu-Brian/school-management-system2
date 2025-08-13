<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    protected $fillable = ['classroom_id', 'year'];

    public function charges()
    {
        return $this->hasMany(FeeCharge::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
    public function votehead()
    {
        return $this->belongsTo(Votehead::class);
    }

    public function feeStructure()
    {
        return $this->belongsTo(FeeStructure::class);
    }

}
