<?php

namespace App\Services;

use App\Services\CommunicationRecipientService;

class CommunicationHelperService
{
    /**
     * Build a map of recipients => entity used for personalization.
     * $target: students|parents|staff|class|student|custom
     * $data: ['target', 'classroom_id', 'student_id', 'custom_emails', 'custom_numbers']
     * $type: 'email' or 'sms'
     */
    public static function collectRecipients(array $data, string $type): array
    {
        /** @var CommunicationRecipientService $service */
        $service = app(CommunicationRecipientService::class);

        return $service->resolveMap($data, $type);
    }
}
