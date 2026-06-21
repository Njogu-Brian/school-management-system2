<?php

namespace App\Services\Admissions;

use App\Models\Academics\Classroom;
use App\Models\Admissions\AdmissionApplication;
use App\Models\Admissions\AdmissionDocument;
use App\Models\ParentInfo;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\User;
use App\Services\FamilyLinkingService;
use App\Services\FeePostingService;
use App\Services\PhoneNumberService;
use App\Services\TransportFeeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdmissionApplicationEnrollmentService
{
    public function __construct(
        private OnlineAdmissionWorkflowService $onlineAdmissionWorkflow,
        private PhoneNumberService $phoneService,
    ) {
    }

    public function enroll(AdmissionApplication $application, array $validated, int $userId): Student
    {
        if ($application->status === AdmissionApplication::STATUS_ENROLLED && $application->student_id) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been enrolled.',
            ]);
        }

        if ($application->status !== AdmissionApplication::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'status' => 'Application must be approved before enrollment.',
            ]);
        }

        $nameParts = $application->childNameParts();

        if ($application->dob) {
            $duplicate = Student::query()
                ->where('first_name', $nameParts['first_name'])
                ->where('last_name', $nameParts['last_name'])
                ->whereDate('dob', $application->dob)
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'student' => 'An active student already exists with the same name and date of birth.',
                ]);
            }
        }

        return DB::transaction(function () use ($application, $validated, $userId, $nameParts) {
            $classroom = Classroom::withCount(['streams', 'primaryStreams'])->findOrFail($validated['classroom_id']);
            $hasStreams = ($classroom->streams_count ?? 0) + ($classroom->primary_streams_count ?? 0) > 0;

            if ($hasStreams && empty($validated['stream_id'])) {
                throw ValidationException::withMessages([
                    'stream_id' => 'Please select a stream for the chosen classroom.',
                ]);
            }

            $phoneResult = $this->phoneService->validateLocalDigitsLength($application->phone, '+254');
            if (! $phoneResult['ok']) {
                throw ValidationException::withMessages(['phone' => 'Phone number is invalid for Kenya (+254).']);
            }

            $formattedPhone = $this->phoneService->formatWithCountryCode($application->phone, '+254');

            $parentData = [
                'father_name' => $application->parent_name,
                'father_phone' => $formattedPhone,
                'father_phone_country_code' => '+254',
                'father_email' => $application->email,
                'primary_contact_person' => $application->parent_name,
            ];

            $linker = app(FamilyLinkingService::class);
            $parent = $linker->findMatchingParent($parentData) ?: ParentInfo::create($parentData);

            $admissionNumber = $this->generateAdmissionNumber();

            $photoPath = $application->documents()
                ->where('document_type', AdmissionDocument::TYPE_PASSPORT_PHOTO)
                ->value('file_path');

            if ($photoPath) {
                $photoPath = 'admissions/'.$photoPath;
            }

            $enrollmentYear = $validated['enrollment_year'] ?? (get_current_academic_year() ?? (int) date('Y'));
            $enrollmentTerm = $validated['enrollment_term'] ?? (get_current_term_number() ?? 1);

            $student = Student::create([
                'admission_number' => $admissionNumber,
                'first_name' => $nameParts['first_name'],
                'middle_name' => $nameParts['middle_name'],
                'last_name' => $nameParts['last_name'],
                'dob' => $application->dob,
                'gender' => $application->gender,
                'classroom_id' => $validated['classroom_id'],
                'stream_id' => $validated['stream_id'] ?? null,
                'category_id' => $validated['category_id'],
                'parent_id' => $parent->id,
                'photo_path' => $photoPath,
                'allergies_notes' => $application->medical_notes,
                'residential_area' => $validated['residential_area'] ?? null,
                'status' => 'active',
                'admission_date' => now()->toDateString(),
                'enrollment_year' => $enrollmentYear,
                'enrollment_term' => $enrollmentTerm,
            ]);

            $linker->ensureFamilyForStudentFromParent($student, $parent);

            $this->ensureParentUserAccount($parent, $application);

            if (! empty($validated['transport_fee_amount'])) {
                TransportFeeService::upsertFee([
                    'student_id' => $student->id,
                    'amount' => $validated['transport_fee_amount'],
                    'source' => 'website_admission',
                    'note' => 'Captured during website admission enrollment',
                    'year' => $enrollmentYear,
                    'term' => $enrollmentTerm,
                ]);
            }

            $application->update([
                'status' => AdmissionApplication::STATUS_ENROLLED,
                'student_id' => $student->id,
                'reviewed_by' => $userId,
                'reviewed_at' => now(),
            ]);

            try {
                FeePostingService::chargeFeesForNewStudent($student, $enrollmentYear, $enrollmentTerm);
            } catch (\Exception $e) {
                Log::warning('Website admission fee posting failed: '.$e->getMessage(), [
                    'student_id' => $student->id,
                    'application_id' => $application->id,
                ]);
            }

            return $student->fresh(['classroom', 'stream']);
        });
    }

    protected function generateAdmissionNumber(): string
    {
        $reflection = new \ReflectionClass($this->onlineAdmissionWorkflow);
        $method = $reflection->getMethod('generateNextAdmissionNumber');
        $method->setAccessible(true);

        return $method->invoke($this->onlineAdmissionWorkflow);
    }

    protected function ensureParentUserAccount(ParentInfo $parent, AdmissionApplication $application): void
    {
        if (! $application->email) {
            return;
        }

        $existing = User::query()->where('email', $application->email)->first();
        if ($existing) {
            if (! $existing->parent_id) {
                $existing->update(['parent_id' => $parent->id]);
            }

            return;
        }

        User::create([
            'name' => $application->parent_name,
            'email' => $application->email,
            'password' => bcrypt(Str::random(16)),
            'parent_id' => $parent->id,
        ])->assignRole('Parent');
    }
}
