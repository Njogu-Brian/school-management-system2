<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class Homework extends Model
{
    protected $table = 'homework';

    protected $fillable = [
        'classroom_id','stream_id','subject_id','teacher_id',
        'title','instructions','due_date','file_path'
    ];

    protected $casts = ['due_date' => 'date'];

    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(\App\Models\Staff::class); }
}
