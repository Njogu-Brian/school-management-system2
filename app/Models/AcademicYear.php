<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['year', 'is_active'];

    public function terms()
    {
        return $this->hasMany(Term::class);
    }
}
