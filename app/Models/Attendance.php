<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance'; // Explicitly define the correct table name
    protected $fillable = ['student_id', 'date', 'is_present', 'reason']; // Added 'reason'

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
