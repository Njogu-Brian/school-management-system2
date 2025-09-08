<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function jobTitles()
    {
        return $this->hasMany(JobTitle::class);
    }

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }
}
