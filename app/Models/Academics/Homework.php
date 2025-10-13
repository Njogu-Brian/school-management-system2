<?php

namespace App\Models\Academics;

use Illuminate\Database\Eloquent\Model;
use App\Models\Staff;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\Academics\Subject;
use App\Models\Student;  

class Homework extends Model
{
    protected $fillable = [
        'assigned_by','teacher_id','classroom_id','stream_id','subject_id',
        'title','instructions','due_date','file_path','target_scope'
    ];

    protected $casts = ['due_date'=>'date'];

    public function teacher()   { return $this->belongsTo(Staff::class,'teacher_id'); }
    public function classroom(){ return $this->belongsTo(Classroom::class); }
    public function stream()   { return $this->belongsTo(Stream::class); }
    public function subject()  { return $this->belongsTo(Subject::class); }
    public function students() { return $this->belongsToMany(Student::class,'homework_student'); }
    public function diary()    { return $this->hasOne(Diary::class,'homework_id'); }
}
