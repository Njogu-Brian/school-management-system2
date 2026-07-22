<?php

namespace App\Services\Admissions;

use App\Models\Academics\Classroom;
use App\Models\CommunicationTemplate;
use App\Models\DropOffPoint;
use App\Models\OnlineAdmission;
use App\Models\ParentInfo;
use App\Models\Setting;
use App\Models\Student;
use App\Services\FamilyLinkingService;
use App\Services\PhoneNumberService;
use App\Services\SMSService;
use App\Services\TransportFeeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Shared online admission workflow (enroll, reject, waitlist, status updates).
 * Used by API mobile workspace; mirrors web OnlineAdmissionController actions.
 */
class OnlineAdmissionWorkflowService
{
    public function updateStatus(OnlineAdmission $admission, array $data, int $userId): OnlineAdmission
    {
        if ($admission->enrolled) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been enrolled.',
            ]);
        }

        $admission->update([
            'application_status' => $data['application_status'],
            'review_notes' => $data['review_notes'] ?? $admission->review_notes,
            'reviewed_by' => $userId,
            'review_date' => now(),
            'classroom_id' => $data['classroom_id'] ?? $admission->classroom_id,
            'stream_id' => $data['stream_id'] ?? $admission->stream_id,
            // Leaving waitlist clears position so UI/filters stay accurate.
            'waitlist_position' => $data['application_status'] === 'waitlisted'
                ? $admission->waitlist_position
                : null,
        ]);

        if ($data['application_status'] === 'waitlisted' && ! $admission->waitlist_position) {
            $maxPosition = OnlineAdmission::where('application_status', 'waitlisted')
                ->max('waitlist_position') ?? 0;
            $admission->update(['waitlist_position' => $maxPosition + 1]);
        }

        return $admission->fresh();
    }

    public function addToWaitlist(OnlineAdmission $admission, ?string $reviewNotes, int $userId): OnlineAdmission
    {
        if ($admission->enrolled) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been enrolled.',
            ]);
        }

        // Idempotent: already waitlisted keeps current position.
        if ($admission->application_status === 'waitlisted' && $admission->waitlist_position) {
            $admission->update([
                'reviewed_by' => $userId,
                'review_date' => now(),
                'review_notes' => $reviewNotes ?? $admission->review_notes ?? 'Added to waiting list',
            ]);

            return $admission->fresh();
        }

        $maxPosition = OnlineAdmission::where('application_status', 'waitlisted')
            ->max('waitlist_position') ?? 0;

        $admission->update([
            'application_status' => 'waitlisted',
            'waitlist_position' => $maxPosition + 1,
            'reviewed_by' => $userId,
            'review_date' => now(),
            'review_notes' => $reviewNotes ?? 'Added to waiting list',
        ]);

        return $admission->fresh();
    }

    public function reject(OnlineAdmission $admission, int $userId): OnlineAdmission
    {
        if ($admission->enrolled) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been enrolled.',
            ]);
        }

        $admission->update([
            'application_status' => 'rejected',
            'reviewed_by' => $userId,
            'review_date' => now(),
            // Keep the row; clear waitlist position when leaving waitlist
            'waitlist_position' => null,
        ]);

        return $admission->fresh();
    }

    public function enroll(OnlineAdmission $admission, array $validated, int $userId): Student
    {
        if ($admission->enrolled) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been processed.',
            ]);
        }

        $admissionDate = ! empty($validated['admission_date'])
            ? \Carbon\Carbon::parse($validated['admission_date'])->toDateString()
            : now()->toDateString();

        if ($admission->dob) {
            $duplicate = Student::query()
                ->where('archive', 0)
                ->where('first_name', trim((string) $admission->first_name))
                ->where('last_name', trim((string) $admission->last_name))
                ->whereDate('dob', $admission->dob)
                ->first();
            if ($duplicate) {
                throw ValidationException::withMessages([
                    'student' => 'An active student already exists with the same name and date of birth (Admission #: '.$duplicate->admission_number.').',
                ]);
            }
        }

        return DB::transaction(function () use ($admission, $validated, $admissionDate, $userId) {
            $classroom = Classroom::withCount(['streams', 'primaryStreams'])->find($validated['classroom_id']);
            $classroomHasStreams = $classroom && (($classroom->streams_count ?? 0) + ($classroom->primary_streams_count ?? 0)) > 0;
            if ($classroomHasStreams && empty($validated['stream_id'])) {
                throw ValidationException::withMessages([
                    'stream_id' => 'Please select a stream for the chosen classroom.',
                ]);
            }

            $phoneSvc = app(PhoneNumberService::class);
            foreach ([
                ['value' => $admission->father_phone, 'cc' => $admission->father_phone_country_code ?? '+254', 'label' => 'Father phone'],
                ['value' => $admission->mother_phone, 'cc' => $admission->mother_phone_country_code ?? '+254', 'label' => 'Mother phone'],
                ['value' => $admission->guardian_phone, 'cc' => $admission->guardian_phone_country_code ?? '+254', 'label' => 'Guardian phone'],
            ] as $rule) {
                if (empty($rule['value'])) {
                    continue;
                }
                $res = $phoneSvc->validateLocalDigitsLength($rule['value'], $rule['cc']);
                if (! $res['ok']) {
                    throw ValidationException::withMessages([
                        'phone' => $rule['label'].' must be '.$res['min'].'-'.$res['max'].' digits for '.$res['code'].'.',
                    ]);
                }
            }

            $phoneFormatter = app(PhoneNumberService::class);
            $formatPhone = fn (?string $number, ?string $code = '+254') => $phoneFormatter->formatWithCountryCode($number, $code);

            $parentData = [
                'father_name' => $admission->father_name,
                'father_phone' => $formatPhone($admission->father_phone, $admission->father_phone_country_code ?? '+254'),
                'father_phone_country_code' => $admission->father_phone_country_code ?? '+254',
                'father_whatsapp' => $formatPhone($admission->father_whatsapp, $admission->father_phone_country_code ?? '+254'),
                'father_email' => $admission->father_email,
                'father_id_number' => $admission->father_id_number,
                'father_id_document' => $admission->father_id_document,
                'mother_name' => $admission->mother_name,
                'mother_phone' => $formatPhone($admission->mother_phone, $admission->mother_phone_country_code ?? '+254'),
                'mother_phone_country_code' => $admission->mother_phone_country_code ?? '+254',
                'mother_whatsapp' => $formatPhone($admission->mother_whatsapp, $admission->mother_phone_country_code ?? '+254'),
                'mother_email' => $admission->mother_email,
                'mother_id_number' => $admission->mother_id_number,
                'mother_id_document' => $admission->mother_id_document,
                'guardian_name' => $admission->guardian_name,
                'guardian_phone' => $formatPhone($admission->guardian_phone, $admission->guardian_phone_country_code ?? '+254'),
                'guardian_phone_country_code' => $admission->guardian_phone_country_code ?? '+254',
                'guardian_relationship' => $admission->guardian_relationship,
                'marital_status' => $admission->marital_status,
            ];

            $linker = app(FamilyLinkingService::class);
            $matched = $linker->findMatchingParent($parentData);
            $parent = $matched ?: ParentInfo::create($parentData);

            $admissionNumber = $this->generateNextAdmissionNumber();

            $dropOffPointLabel = null;
            if (! empty($validated['drop_off_point_other'])) {
                $dropOffPointLabel = $validated['drop_off_point_other'];
            } elseif (! empty($validated['drop_off_point_id'])) {
                $dropOffPointLabel = optional(DropOffPoint::find($validated['drop_off_point_id']))->name;
            }

            $photoPath = null;
            if ($admission->passport_photo && storage_public()->exists($admission->passport_photo)) {
                $newPath = 'students/photos/'.basename($admission->passport_photo);
                if (storage_public()->copy($admission->passport_photo, $newPath)) {
                    $photoPath = $newPath;
                } else {
                    $photoPath = $admission->passport_photo;
                }
            }

            $enrollmentYear = $validated['enrollment_year'] ?? null;
            $enrollmentTerm = $validated['enrollment_term'] ?? null;

            $student = Student::create([
                'admission_number' => $admissionNumber,
                'first_name' => $admission->first_name,
                'middle_name' => $admission->middle_name,
                'last_name' => $admission->last_name,
                'dob' => $admission->dob,
                'gender' => $admission->gender,
                'classroom_id' => $validated['classroom_id'],
                'stream_id' => $validated['stream_id'] ?? null,
                'category_id' => $validated['category_id'],
                'trip_id' => $validated['trip_id'] ?? null,
                'drop_off_point_id' => $validated['drop_off_point_id'] ?? null,
                'drop_off_point_other' => $validated['drop_off_point_other'] ?? null,
                'drop_off_point' => $dropOffPointLabel,
                'parent_id' => $parent->id,
                'nemis_number' => $admission->nemis_number,
                'knec_assessment_number' => $admission->knec_assessment_number,
                'marital_status' => $admission->marital_status,
                'photo_path' => $photoPath,
                'has_allergies' => isset($validated['has_allergies']) ? (bool) $validated['has_allergies'] : (bool) $admission->has_allergies,
                'allergies_notes' => $validated['allergies_notes'] ?? $admission->allergies_notes,
                'is_fully_immunized' => isset($validated['is_fully_immunized']) ? (bool) $validated['is_fully_immunized'] : (bool) $admission->is_fully_immunized,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? $admission->emergency_contact_name,
                'emergency_contact_phone' => $formatPhone(
                    $validated['emergency_contact_phone'] ?? $admission->emergency_contact_phone,
                    '+254'
                ),
                'preferred_hospital' => $validated['preferred_hospital'] ?? $admission->preferred_hospital,
                'residential_area' => $validated['residential_area'] ?? $admission->residential_area,
                'status' => 'active',
                'admission_date' => $admissionDate,
                'enrollment_year' => $enrollmentYear,
                'enrollment_term' => $enrollmentTerm,
            ]);

            $linker->ensureFamilyForStudentFromParent($student, $parent);

            if (! empty($validated['transport_fee_amount'])) {
                $transportYear = $enrollmentYear ?? (get_current_academic_year() ?? (int) date('Y'));
                $transportTerm = $enrollmentTerm ?? (get_current_term_number() ?? 1);
                TransportFeeService::upsertFee([
                    'student_id' => $student->id,
                    'amount' => $validated['transport_fee_amount'],
                    'drop_off_point_id' => $validated['drop_off_point_id'] ?? null,
                    'drop_off_point_name' => $dropOffPointLabel,
                    'source' => 'online_admission',
                    'note' => 'Captured during online admission approval',
                    'year' => $transportYear,
                    'term' => $transportTerm,
                ]);
            }

            $admission->update([
                'enrolled' => true,
                'application_status' => 'enrolled',
                'reviewed_by' => $userId,
                'review_date' => now(),
                'classroom_id' => $validated['classroom_id'],
                'stream_id' => $validated['stream_id'] ?? null,
                'waitlist_position' => null,
            ]);

            try {
                $this->sendAdmissionCommunication($student->load(['classroom', 'stream']), $parent);
            } catch (\Exception $e) {
                Log::warning('Failed to send admission welcome messages: '.$e->getMessage(), [
                    'student_id' => $student->id,
                    'admission_id' => $admission->id,
                ]);
            }

            try {
                $feeYear = $enrollmentYear ?? (get_current_academic_year() ?? (int) date('Y'));
                $feeTerm = $enrollmentTerm ?? (get_current_term_number() ?? 1);
                \App\Services\FeePostingService::chargeFeesForNewStudent($student, $feeYear, $feeTerm);
            } catch (\Exception $e) {
                Log::warning('Failed to charge fees for new student from online admission: '.$e->getMessage(), [
                    'student_id' => $student->id,
                    'admission_id' => $admission->id,
                ]);
            }

            return $student->fresh(['classroom', 'stream']);
        });
    }

    protected function generateNextAdmissionNumber(): string
    {
        $prefix = (string) Setting::get('student_id_prefix', 'RKS');
        $start = Setting::getInt('student_id_start', 1);
        $prefixLen = strlen($prefix);
        $maxNumeric = (int) Student::query()
            ->whereNotNull('admission_number')
            ->where('admission_number', 'like', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(admission_number, ?) AS UNSIGNED)) as m', [$prefixLen + 1])
            ->value('m');

        $currentCounter = Setting::getInt('student_id_counter', $start);
        if ($maxNumeric > 0 && $currentCounter < $maxNumeric) {
            Setting::setInt('student_id_counter', $maxNumeric);
        }

        for ($i = 0; $i < 20; $i++) {
            $counter = Setting::incrementValue('student_id_counter', 1, max($start, $maxNumeric));
            $candidate = $prefix.(string) $counter;
            if (! Student::query()->where('admission_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix.(string) time();
    }

    protected function sendAdmissionCommunication(Student $student, ParentInfo $parent): void
    {
        $smsTemplate = CommunicationTemplate::where('code', 'admissions_welcome_sms')->first();
        $emailTemplate = CommunicationTemplate::where('code', 'admissions_welcome_email')->first();
        $whatsappTemplate = CommunicationTemplate::where('code', 'admissions_welcome_whatsapp')->first();

        $schoolName = DB::table('settings')->where('key', 'school_name')->value('value') ?? config('app.name', 'School');
        $schoolPhone = DB::table('settings')->where('key', 'school_phone')->value('value') ?? '';
        $schoolEmail = DB::table('settings')->where('key', 'school_email')->value('value') ?? '';

        $parentName = $parent->primary_contact_name ?? $parent->father_name ?? $parent->mother_name ?? $parent->guardian_name ?? 'Parent';
        $variables = [
            'parent_name' => $parentName,
            'student_name' => $student->full_name ?? trim($student->first_name.' '.$student->last_name),
            'admission_number' => $student->admission_number ?? '',
            'class_name' => $student->classroom?->name ?? '',
            'stream_name' => $student->stream?->name ?? '',
            'school_name' => $schoolName,
            'school_phone' => $schoolPhone,
            'school_email' => $schoolEmail,
            'profile_update_link' => url('/parent/profile'),
        ];

        $replacePlaceholders = function ($text, $vars) {
            foreach ($vars as $key => $value) {
                $text = str_replace('{{'.$key.'}}', (string) $value, $text);
            }

            return $text;
        };

        app(SMSService::class);
        $parentNotify = app(\App\Services\ParentSchoolNotificationService::class);
        unset($variables['parent_name']);

        if ($smsTemplate) {
            $smsBody = $replacePlaceholders($smsTemplate->content, $variables);
            $parentNotify->sendSmsTemplateToStudentParents($student, $smsBody, $smsTemplate->title ?? 'Admission');
        }
        if ($emailTemplate) {
            $subjectTpl = $replacePlaceholders($emailTemplate->subject ?? $emailTemplate->title, $variables);
            $bodyTpl = $replacePlaceholders($emailTemplate->content, $variables);
            $parentNotify->sendEmailTemplateToStudentParents($student, $subjectTpl, $bodyTpl);
        }
        if ($whatsappTemplate) {
            $waBody = $replacePlaceholders($whatsappTemplate->content, $variables);
            $parentNotify->sendWhatsAppTemplateToStudentParents($student, $waBody, $whatsappTemplate->title ?? 'Admission');
        }
    }
}
