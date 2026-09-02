<?php

namespace App\Support;

class FamilyUpdateFeedback
{
    /**
     * Human-readable label for a validation field key.
     */
    public static function fieldLabel(string $key): string
    {
        if (preg_match('/^students\.(\d+)\.(.+)$/u', $key, $matches)) {
            $field = self::baseLabel($matches[2]);

            return $field.' (child record)';
        }

        return self::baseLabel($key);
    }

    /**
     * Turn Laravel validation messages into parent-friendly copy.
     */
    public static function friendlyMessage(string $message): string
    {
        $replacements = [
            'The residential area field is required.' => 'Please enter your residential area.',
            'The emergency contact name field is required.' => 'Please enter an emergency contact name.',
            'The emergency contact phone field is required.' => 'Please enter an emergency contact phone number.',
            'The preferred hospital field is required.' => 'Please enter your preferred hospital or clinic.',
            'Select at least one learner interest.' => 'Please select at least one learner interest.',
            'Complete all father details or all mother details. ID document upload is optional.' => 'Please complete all father details or all mother details (ID upload is optional).',
            'This field is required to complete father details.' => 'This is needed to complete the father’s details.',
            'This field is required to complete mother details.' => 'This is needed to complete the mother’s details.',
            'The father id document field must be a file of type: pdf, jpg, jpeg, png.' => 'Father ID must be a PDF or image (JPG/PNG).',
            'The mother id document field must be a file of type: pdf, jpg, jpeg, png.' => 'Mother ID must be a PDF or image (JPG/PNG).',
            'The father id document field must not be greater than 10240 kilobytes.' => 'Father ID file is too large (max 10 MB). Try a clearer, smaller photo.',
            'The mother id document field must not be greater than 10240 kilobytes.' => 'Mother ID file is too large (max 10 MB). Try a clearer, smaller photo.',
        ];

        if (isset($replacements[$message])) {
            return $replacements[$message];
        }

        if (preg_match('/must not be greater than (\d+) kilobytes/u', $message, $sizeMatch)) {
            $mb = max(1, (int) round(((int) $sizeMatch[1]) / 1024));
            if (str_contains($message, 'passport photo')) {
                return "That photo is too large (max {$mb} MB). Use a smaller picture — the form also compresses photos automatically.";
            }
            if (str_contains($message, 'birth certificate') || str_contains($message, 'id document')) {
                return "That file is too large (max {$mb} MB). Please upload a smaller PDF or photo.";
            }

            return "That file is too large (max {$mb} MB). Please choose a smaller file.";
        }

        if (preg_match('/^The students\.\d+\.(.+) field is required\.$/u', $message, $matches)) {
            $label = strtolower(self::baseLabel($matches[1]));
            if ($matches[1] === 'medical_condition') {
                return 'Please enter a medical condition, or type None if there is none.';
            }
            if ($matches[1] === 'middle_name') {
                return 'Middle name is optional — you can leave it blank.';
            }

            return 'Please enter '.$label.'.';
        }

        if (preg_match('/^The students\.\d+\.allergies_notes field is required when students\.\d+\.has_allergies is 1\.$/u', $message)) {
            return 'Please describe the allergies when “Has allergies” is checked.';
        }

        if (str_contains($message, 'field is required')) {
            return 'This field is required.';
        }

        return $message;
    }

    private static function baseLabel(string $key): string
    {
        static $labels = [
            'residential_area' => 'Residential area',
            'emergency_contact_name' => 'Emergency contact name',
            'emergency_contact_phone' => 'Emergency contact phone',
            'preferred_hospital' => 'Preferred hospital',
            'marital_status' => 'Marital status',
            'school_notifications_muted_parent' => 'School notifications',
            'parent_required' => 'Parent details',
            'father_first_name' => 'Father first name',
            'father_middle_name' => 'Father middle name',
            'father_last_name' => 'Father last name',
            'father_id_type' => 'Father ID type',
            'father_id_number' => 'Father ID number',
            'father_country_of_residence' => 'Father country of residence',
            'father_phone' => 'Father phone',
            'father_whatsapp' => 'Father WhatsApp',
            'father_email' => 'Father email',
            'father_id_document' => 'Father ID document',
            'mother_first_name' => 'Mother first name',
            'mother_middle_name' => 'Mother middle name',
            'mother_last_name' => 'Mother last name',
            'mother_id_type' => 'Mother ID type',
            'mother_id_number' => 'Mother ID number',
            'mother_country_of_residence' => 'Mother country of residence',
            'mother_phone' => 'Mother phone',
            'mother_whatsapp' => 'Mother WhatsApp',
            'mother_email' => 'Mother email',
            'mother_id_document' => 'Mother ID document',
            'guardian_first_name' => 'Guardian first name',
            'guardian_last_name' => 'Guardian last name',
            'guardian_phone' => 'Guardian phone',
            'guardian_email' => 'Guardian email',
            'first_name' => 'First name',
            'middle_name' => 'Middle name',
            'last_name' => 'Last name',
            'gender' => 'Sex',
            'dob' => 'Date of birth',
            'nationality' => 'Nationality',
            'county_of_birth' => 'County of birth',
            'sub_county_of_birth' => 'Sub-county of birth',
            'location_of_birth' => 'Location of birth',
            'birth_certificate_entry_no' => 'Birth certificate entry number',
            'medical_condition' => 'Medical condition',
            'religion' => 'Religion',
            'orphan_status' => 'Orphan status',
            'learner_interests' => 'Learner interests',
            'has_special_needs' => 'Special needs',
            'disability_type' => 'Disability type',
            'allergies_notes' => 'Allergy notes',
            'passport_photo' => 'Passport photo',
            'birth_certificate' => 'Birth certificate',
            'students' => 'Student details',
        ];

        return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Convert a Laravel error key to an HTML input name attribute.
     */
    public static function keyToInputName(string $key): ?string
    {
        if (preg_match('/^students\.(\d+)\.(.+)$/u', $key, $matches)) {
            return 'students['.$matches[1].']['.$matches[2].']';
        }

        if (str_contains($key, '.')) {
            return null;
        }

        return $key;
    }
}
