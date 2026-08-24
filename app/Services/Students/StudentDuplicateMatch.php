<?php

namespace App\Services\Students;

class StudentDuplicateMatch
{
    public const SOURCE_STUDENT = 'student';

    public const SOURCE_ONLINE_ADMISSION = 'online_admission';

    public const SOURCE_WEBSITE_APPLICATION = 'admission_application';

    public const CONFIDENCE_HIGH = 'high';

    public const CONFIDENCE_MEDIUM = 'medium';

    public function __construct(
        public string $source,
        public string $reason,
        public string $reasonLabel,
        public string $confidence,
        public string $fullName,
        public string $sourceLabel,
        public ?int $studentId = null,
        public ?string $admissionNumber = null,
        public ?string $status = null,
        public ?string $classroom = null,
        public ?string $url = null,
        public ?int $applicationId = null,
        public ?string $applicationNo = null,
        public ?string $applicationStatus = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'reason' => $this->reason,
            'reason_label' => $this->reasonLabel,
            'confidence' => $this->confidence,
            'full_name' => $this->fullName,
            'source_label' => $this->sourceLabel,
            'student_id' => $this->studentId,
            'admission_number' => $this->admissionNumber,
            'status' => $this->status,
            'classroom' => $this->classroom,
            'url' => $this->url,
            'application_id' => $this->applicationId,
            'application_no' => $this->applicationNo,
            'application_status' => $this->applicationStatus,
        ];
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence === self::CONFIDENCE_HIGH;
    }
}
