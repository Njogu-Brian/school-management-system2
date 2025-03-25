<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentInfo extends Model
{
    protected $table = 'parent_info'; // Explicitly set the table name

    protected $fillable = ['name', 'phone'];
    
    public function students()
{
    return $this->hasMany(Student::class, 'parent_id');
}
}