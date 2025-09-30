<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class ReportCardSkill extends Model
{
    protected $fillable = ['report_card_id','skill_name','rating'];

    public function reportCard() { return $this->belongsTo(ReportCard::class); }
}
