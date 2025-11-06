<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class GradingSchemeMapping extends Model
{
    protected $fillable = ['grading_scheme_id','classroom_id','level_key'];

    public function scheme(){ return $this->belongsTo(GradingScheme::class,'grading_scheme_id'); }
}
