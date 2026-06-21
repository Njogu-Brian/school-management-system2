<?php

namespace App\Services\Website;

use App\Models\Admissions\AdmissionApplication;
use App\Models\Website\CampaignLog;
use App\Services\CommunicationService;

class CampaignAutomationService
{
    public function sendAbandonedAdmissionReminders(): int
    {
        $applications = AdmissionApplication::query()
            ->where('status', 'pending')
            ->where('current_step', '<', 4)
            ->where('updated_at', '<', now()->subDays(2))
            ->get();

        $sent = 0;
        $communication = app(CommunicationService::class);

        foreach ($applications as $application) {
            if (! $application->email || str_contains($application->email, '@pending.local')) {
                continue;
            }

            $body = "Dear {$application->parent_name}, you started an admission application ({$application->application_no}) at Royal Kings. Complete it here: ".url('/admissions/apply?token='.$application->draft_token);

            try {
                $communication->sendEmail('admission_reminder', $application->id, $application->email, $body, 'Complete Your Royal Kings Application');
                $sent++;
            } catch (\Throwable) {
                continue;
            }
        }

        CampaignLog::create([
            'campaign_name' => 'Abandoned Admission Reminders',
            'type' => 'email',
            'audience' => 'incomplete_applications',
            'sent_count' => $sent,
            'status' => 'sent',
        ]);

        return $sent;
    }
}
