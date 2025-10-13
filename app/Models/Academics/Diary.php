<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;

class Diary extends Model
{
    protected $fillable = ['classroom_id','stream_id','teacher_id','week_start','entries','homework_id','is_homework'];

    protected $casts = ['week_start'=>'date','entries'=>'array'];

    public function teacher(){ return $this->belongsTo(\App\Models\Staff::class,'teacher_id'); }
    public function classroom(){ return $this->belongsTo(Classroom::class); }
    public function homework(){ return $this->belongsTo(Homework::class); }
    public function messages(){ return $this->hasMany(DiaryMessage::class); }
}


