<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    protected $fillable = [
        'guardian_name',
        'phone',
        'email',
    ];

    public function students()
{
    return $this->hasMany(Student::class);
}

}
