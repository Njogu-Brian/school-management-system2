<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class GradingBand extends Model
{
    protected $fillable = ['grading_scheme_id','min','max','label','descriptor','rank'];

    public function scheme() {
        return $this->belongsTo(GradingScheme::class,'grading_scheme_id');
    }
}
