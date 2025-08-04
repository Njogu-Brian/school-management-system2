<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $fillable = ['name', 'academic_year_id', 'is_current'];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
