<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentInfo extends Model
{
    protected $table = 'parent_info';

    protected $fillable = [
        'father_name', 'father_phone', 'father_whatsapp', 'father_email', 'father_id_number',
        'mother_name', 'mother_phone', 'mother_whatsapp', 'mother_email', 'mother_id_number',
        'guardian_name', 'guardian_phone', 'guardian_whatsapp', 'guardian_email',
        'guardian_relationship', 'marital_status',
        'father_phone_country_code', 'mother_phone_country_code', 'guardian_phone_country_code',
        'father_whatsapp_country_code', 'mother_whatsapp_country_code',
        'father_id_document', 'mother_id_document',
        // Extended parent info
        'father_occupation', 'father_employer', 'father_work_address', 'father_education_level',
        'mother_occupation', 'mother_employer', 'mother_work_address', 'mother_education_level',
        'guardian_occupation', 'guardian_employer', 'guardian_work_address', 'guardian_education_level',
        'family_income_bracket', 'primary_contact_person', 'communication_preference', 'language_preference'
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    public function getPrimaryContactNameAttribute(): ?string
    {
        return $this->father_name
            ?? $this->mother_name
            ?? $this->guardian_name;
    }

    public function getPrimaryContactPhoneAttribute(): ?string
    {
        return $this->father_phone
            ?? $this->mother_phone
            ?? $this->guardian_phone;
    }

    public function getPrimaryContactEmailAttribute(): ?string
    {
        return $this->father_email
            ?? $this->mother_email
            ?? $this->guardian_email;
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
