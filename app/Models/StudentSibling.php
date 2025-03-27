<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentSibling extends Model
{
    protected $table = 'student_siblings';
    protected $fillable = ['student_id', 'sibling_id'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function sibling()
    {
        return $this->belongsTo(Student::class, 'sibling_id');
    }
}
