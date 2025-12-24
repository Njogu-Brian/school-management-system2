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
        'title','instructions','due_date','file_path','target_scope',
        'lesson_plan_id','scheme_of_work_id','attachment_paths',
        'allow_late_submission','max_score'
    ];

    protected $casts = [
        'due_date' => 'date',
        'attachment_paths' => 'array',
        'allow_late_submission' => 'boolean',
        'max_score' => 'integer',
    ];

    public function teacher()   
    { 
        return $this->belongsTo(Staff::class,'teacher_id'); 
    }
    
    public function classroom()
    { 
        return $this->belongsTo(Classroom::class); 
    }
    
    public function stream()   
    { 
        return $this->belongsTo(Stream::class); 
    }
    
    public function subject()  
    { 
        return $this->belongsTo(Subject::class); 
    }
    
    public function students() 
    { 
        return $this->belongsToMany(Student::class,'homework_student'); 
    }
    
    public function diary()    
    { 
        return $this->hasOne(Diary::class,'homework_id'); 
    }

    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class, 'lesson_plan_id');
    }

    public function schemeOfWork()
    {
        return $this->belongsTo(SchemeOfWork::class, 'scheme_of_work_id');
    }

    public function homeworkDiaries()
    {
        return $this->hasMany(HomeworkDiary::class, 'homework_id');
    }

    /**
     * Backward-compatible alias used in some controllers/queries.
     */
    public function homeworkDiary()
    {
        return $this->homeworkDiaries();
    }

    public function submittedHomework()
    {
        return $this->homeworkDiaries()->whereIn('status', ['submitted', 'marked']);
    }

    public function markedHomework()
    {
        return $this->homeworkDiaries()->where('status', 'marked');
    }
}
