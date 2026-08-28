<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineAdmission extends Model
{
    use HasFactory;
    use \App\Models\Concerns\NormalizesNameAttributes;

    protected static array $sentenceCaseNameAttributes = [
        'first_name',
        'middle_name',
        'last_name',
        'father_name',
        'mother_name',
        'guardian_name',
        'emergency_contact_name',
    ];

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'dob', 'gender',
        'nationality', 'county_of_birth', 'sub_county_of_birth', 'location_of_birth',
        'birth_certificate_entry_no', 'medical_condition', 'religion', 'learner_interests',
        'orphan_status', 'has_special_needs', 'disability_type',
        'father_name', 'mother_name', 'guardian_name',
        'father_first_name', 'father_middle_name', 'father_last_name',
        'mother_first_name', 'mother_middle_name', 'mother_last_name',
        'guardian_first_name', 'guardian_middle_name', 'guardian_last_name',
        'father_phone', 'mother_phone', 'guardian_phone',
        'father_whatsapp', 'mother_whatsapp', 'guardian_whatsapp',
        'father_email', 'mother_email', 'guardian_email',
        'father_id_number', 'mother_id_number', 'guardian_id_number',
        'father_id_type', 'mother_id_type', 'guardian_id_type',
        'father_country_of_residence', 'mother_country_of_residence', 'guardian_country_of_residence',
        'nemis_number', 'knec_assessment_number', 'passport_photo',
        'birth_certificate', 'parent_id_card', 'form_status', 'payment_status', 'enrolled',
        'application_status', 'waitlist_position', 'reviewed_by', 'review_notes',
        'application_date', 'review_date', 'classroom_id', 'stream_id',
        'application_source', 'application_notes',
        'preferred_classroom_id', 'transport_needed', 'drop_off_point_id', 'drop_off_point_other',
        'trip_id',
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
        'has_special_needs' => 'boolean',
        'learner_interests' => 'array',
    ];

    public function reviewedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function preferredClassroom()
    {
        return $this->belongsTo(\App\Models\Academics\Classroom::class, 'preferred_classroom_id');
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
