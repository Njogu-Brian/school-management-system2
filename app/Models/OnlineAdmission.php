<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineAdmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'dob', 'gender',
        'father_name', 'mother_name', 'guardian_name', 'father_phone',
        'mother_phone', 'guardian_phone', 'father_email', 'mother_email',
        'guardian_email', 'father_id_number', 'mother_id_number', 'guardian_id_number',
        'nemis_number', 'knec_assessment_number', 'passport_photo',
        'birth_certificate', 'parent_id_card', 'form_status', 'payment_status', 'enrolled'
    ];
}
