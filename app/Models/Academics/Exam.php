<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'name','type','modality','academic_year_id','term_id',
        'classroom_id','stream_id','subject_id','created_by',
        'starts_on','ends_on','max_marks','weight',
        'status','published_at','locked_at','settings'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'locked_at' => 'datetime',
        'settings' => 'array',
    ];

    public function items() { return $this->hasMany(ExamItem::class); }
    public function marks() { return $this->hasMany(ExamMark::class); }
}
