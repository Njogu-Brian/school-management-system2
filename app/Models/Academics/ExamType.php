<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ExamType extends Model
{
    protected $fillable = [
        'name','code','calculation_method','default_min_mark','default_max_mark'
    ];

    public function groups()
    {
        return $this->hasMany(ExamGroup::class);
    }
}
