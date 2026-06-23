<?php

namespace App\Services\Admissions;

use App\Models\Admissions\AdmissionApplication;
use App\Services\CommunicationService;
use App\Services\SystemAlertService;
use Illuminate\Support\Facades\Log;

class AdmissionApplicationNotificationService
{
    public function notifyOnSubmit(AdmissionApplication $application): void
    {
        $application->loadMissing('preferredClassroom');

        $this->notifyParent($application);
        $this->notifyStaff($application);
    }

    protected function notifyParent(AdmissionApplication $application): void
    {
        $classLabel = $application->preferredClassroom?->name
            ?? $application->desired_class
            ?? 'your selected class';
        $termLabel = $application->enrollment_term && $application->enrollment_year
            ? "Term {$application->enrollment_term} {$application->enrollment_year}"
            : 'the upcoming term';

        $parentName = $application->parent_name ?: 'Parent';
        $childName = $application->child_name ?: 'your child';
        $appNo = $application->application_no;

        $sms = "Royal Kings: Thank you {$parentName}. We received {$childName}'s application ({$appNo}) for {$classLabel}, {$termLabel}. We will contact you shortly.";

        $emailSubject = 'Application Received — Royal Kings Premier School';
        $emailHtml = '<p>Dear '.e($parentName).',</p>'
            .'<p>Thank you for applying to <strong>Royal Kings Premier School</strong>.</p>'
            .'<p>We have received your application for <strong>'.e($childName).'</strong> '
            .'(Ref: <strong>'.e($appNo).'</strong>) for <strong>'.e($classLabel).'</strong>, '
            .e($termLabel).'.</p>'
            .'<p>Our admissions team will review your application and contact you shortly to discuss the next steps.</p>'
            .'<p>You can track your application at: '
            .'<a href="'.e(url('/website/admissions/track?no='.$appNo)).'">Track application</a></p>'
            .'<p>Warm regards,<br>Royal Kings Admissions Team</p>';

        $communication = app(CommunicationService::class);

        if ($application->phone && $application->phone !== '0000000000') {
            try {
                $communication->sendSMS(
                    'admission_application',
                    $application->id,
                    $application->phone,
                    $sms,
                    'Application Received'
                );
            } catch (\Throwable $e) {
                Log::warning('Admission submit SMS failed', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($application->email && ! str_contains($application->email, '@pending.local')) {
            try {
                $communication->sendEmail(
                    'admission_application',
                    $application->id,
                    $application->email,
                    $emailSubject,
                    $emailHtml
                );
            } catch (\Throwable $e) {
                Log::warning('Admission submit email failed', [
                    'application_id' => $application->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function notifyStaff(AdmissionApplication $application): void
    {
        $classLabel = $application->preferredClassroom?->name
            ?? $application->desired_class
            ?? 'class not specified';
        $termLabel = $application->enrollment_term && $application->enrollment_year
            ? "Term {$application->enrollment_term} {$application->enrollment_year}"
            : 'term not specified';

        app(SystemAlertService::class)->raiseForRoles(
            ['Super Admin', 'Secretary'],
            title: 'New admission application',
            message: "{$application->child_name} ({$application->application_no}) — {$classLabel}, {$termLabel}. Parent: {$application->parent_name}, {$application->phone}. Please call to follow up.",
            category: 'admissions',
            severity: 'warning',
            fingerprint: 'admission_application_'.$application->id,
            deepLink: '/website-cms/admissions/'.$application->id,
            metadata: [
                'application_id' => $application->id,
                'application_no' => $application->application_no,
                'parent_phone' => $application->phone,
            ],
            push: true,
            escalate: false,
        );
    }
}
