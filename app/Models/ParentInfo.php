<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentInfo extends Model
{
    protected $table = 'parent_info';

    protected $fillable = [
        'father_name', 'father_phone', 'father_whatsapp', 'father_email', 'father_id_number',
        'mother_name', 'mother_phone', 'mother_whatsapp', 'mother_email', 'mother_id_number',
        'guardian_name', 'guardian_phone', 'guardian_whatsapp', 'guardian_email', 'guardian_id_number'
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}
