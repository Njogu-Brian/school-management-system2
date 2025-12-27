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
        'birth_certificate', 'parent_id_card', 'form_status', 'payment_status', 'enrolled',
        'application_status', 'waitlist_position', 'reviewed_by', 'review_notes',
        'application_date', 'review_date', 'classroom_id', 'stream_id',
        'application_source', 'application_notes',
        'preferred_classroom_id', 'transport_needed', 'drop_off_point_id', 'drop_off_point_other',
        'route_id', 'trip_id',
        'has_allergies', 'allergies_notes', 'is_fully_immunized',
        'emergency_contact_name', 'emergency_contact_phone',
        'residential_area', 'preferred_hospital',
        'father_phone_country_code', 'mother_phone_country_code', 'guardian_phone_country_code',
        'father_id_document', 'mother_id_document',
        'previous_school', 'transfer_reason', 'marital_status',
    ];

    protected $casts = [
        'application_date' => 'date',
        'review_date' => 'date',
        'dob' => 'date',
        'enrolled' => 'boolean',
        'transport_needed' => 'boolean',
    ];

    public function reviewedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function classroom()
    {
        return $this->belongsTo(\App\Models\Academics\Classroom::class);
    }

    public function stream()
    {
        return $this->belongsTo(\App\Models\Academics\Stream::class);
    }
}
