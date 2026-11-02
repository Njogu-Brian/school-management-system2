<?php

namespace App\Services\Admissions;

use App\Models\OnlineAdmission;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AdmissionFormService
{
    public const SETTING_FEES_POLICY = 'admission_form_fees_policy';

    public const SETTING_DRESS_CODE = 'admission_form_dress_code';

    public const SETTING_OTHER_POLICIES = 'admission_form_other_policies';

    public const SETTING_INTRO = 'admission_form_intro';

    public function policyBlocks(): array
    {
        return [
            'intro' => Setting::get(self::SETTING_INTRO, 'Please read the school policies below carefully before signing.'),
            'fees_policy' => Setting::get(self::SETTING_FEES_POLICY, "School fees are payable in full by the start of each term unless a payment plan has been agreed in writing with the school accounts office.\n\nAll payments should be made through the school's official paybill. Always keep your M-Pesa confirmation message."),
            'dress_code' => Setting::get(self::SETTING_DRESS_CODE, "Learners are expected to wear the official school uniform on all school days.\n\nHair, shoes, and accessories must follow the school dress code guidelines."),
            'other_policies' => Setting::get(self::SETTING_OTHER_POLICIES, "The school reserves the right to review placement after assessment.\n\nParents/guardians agree to support the school's code of conduct and communication channels."),
        ];
    }

    public function updatePolicies(array $data): array
    {
        $map = [
            'intro' => self::SETTING_INTRO,
            'fees_policy' => self::SETTING_FEES_POLICY,
            'dress_code' => self::SETTING_DRESS_CODE,
            'other_policies' => self::SETTING_OTHER_POLICIES,
        ];

        foreach ($map as $field => $key) {
            if (array_key_exists($field, $data)) {
                Setting::set($key, (string) $data[$field]);
            }
        }

        return $this->policyBlocks();
    }

    public function updateAdmissionFields(OnlineAdmission $admission, array $data): OnlineAdmission
    {
        $fillable = [
            'first_name', 'middle_name', 'last_name', 'dob', 'gender',
            'nationality', 'county_of_birth', 'sub_county_of_birth', 'location_of_birth',
            'birth_certificate_entry_no', 'medical_condition', 'religion',
            'previous_school', 'transfer_reason', 'marital_status',
            'father_name', 'mother_name', 'guardian_name',
            'father_phone', 'mother_phone', 'guardian_phone',
            'father_whatsapp', 'mother_whatsapp', 'guardian_whatsapp',
            'father_email', 'mother_email', 'guardian_email',
            'father_id_number', 'mother_id_number', 'guardian_id_number',
            'father_id_type', 'mother_id_type', 'guardian_id_type',
            'father_phone_country_code', 'mother_phone_country_code', 'guardian_phone_country_code',
            'preferred_classroom_id', 'classroom_id',
            'has_allergies', 'allergies_notes', 'is_fully_immunized',
            'emergency_contact_name', 'emergency_contact_phone',
            'residential_area', 'preferred_hospital',
            'transport_needed', 'nemis_number', 'knec_assessment_number',
            'application_notes',
        ];

        $payload = [];
        foreach ($fillable as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $data[$key];
            }
        }

        if ($payload !== []) {
            $payload['admission_form_filled_at'] = now();
            if (($admission->application_status === 'interviewed'
                || $admission->application_status === 'under_review'
                || $admission->application_status === 'contacted')
                && empty($data['keep_status'])) {
                $payload['application_status'] = 'ready_to_enroll';
                $payload['pipeline_stage'] = 'admission_form';
            }
            $admission->update($payload);
        }

        return $admission->fresh();
    }

    public function generatePdf(OnlineAdmission $admission)
    {
        $admission->load(['preferredClassroom', 'classroom']);
        $policies = $this->policyBlocks();
        $schoolName = Setting::get('school_name', config('app.name', 'School'));
        $schoolPhone = Setting::get('school_phone', '');
        $schoolEmail = Setting::get('school_email', '');

        $html = view('online_admissions.pdf.admission_form', [
            'admission' => $admission,
            'policies' => $policies,
            'schoolName' => $schoolName,
            'schoolPhone' => $schoolPhone,
            'schoolEmail' => $schoolEmail,
            'childName' => trim(implode(' ', array_filter([
                $admission->first_name,
                $admission->middle_name,
                $admission->last_name,
            ]))),
            'className' => $admission->preferredClassroom?->name ?? $admission->classroom?->name,
        ])->render();

        return Pdf::loadHTML($html)->setPaper('A4', 'portrait');
    }

    public function storeSignedForm(OnlineAdmission $admission, UploadedFile $file): OnlineAdmission
    {
        $path = $file->store('admissions/signed-forms', 'public');
        $admission->update([
            'signed_admission_form' => $path,
            'pipeline_stage' => 'docs',
        ]);

        return $admission->fresh();
    }

    public function storeDocument(OnlineAdmission $admission, UploadedFile $file, string $field): OnlineAdmission
    {
        $allowed = [
            'passport_photo',
            'birth_certificate',
            'father_id_document',
            'mother_id_document',
            'parent_id_card',
            'signed_admission_form',
        ];

        if (! in_array($field, $allowed, true)) {
            throw new \InvalidArgumentException('Invalid document field.');
        }

        if ($field === 'passport_photo' || $field === 'signed_admission_form') {
            $path = $file->store('admissions/'.$field, 'public');
        } else {
            $path = function_exists('storage_private')
                ? storage_private()->putFile('admissions/'.$field, $file)
                : $file->store('admissions/'.$field, 'local');
        }

        $admission->update([$field => $path]);

        return $admission->fresh();
    }
}
