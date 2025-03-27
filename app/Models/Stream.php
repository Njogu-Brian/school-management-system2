<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    protected $fillable = ['name', 'class_id'];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
