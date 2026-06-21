<?php

namespace App\Services\Website;

use App\Models\Website\CampaignLog;
use App\Models\Website\NewsletterSubscriber;
use App\Services\CommunicationService;

class NewsletterService
{
    public function subscribe(string $email, string $source = 'website'): NewsletterSubscriber
    {
        return NewsletterSubscriber::query()->updateOrCreate(
            ['email' => $email],
            ['status' => 'active', 'source' => $source]
        );
    }

    public function sendCampaign(string $name, string $subject, string $body, string $audience = 'newsletter'): CampaignLog
    {
        $log = CampaignLog::create([
            'campaign_name' => $name,
            'type' => 'email',
            'audience' => $audience,
            'status' => 'scheduled',
        ]);

        $recipients = NewsletterSubscriber::query()->where('status', 'active')->pluck('email');
        $sent = 0;
        $communication = app(CommunicationService::class);

        foreach ($recipients as $email) {
            try {
                $communication->sendEmail('newsletter', 0, $email, $body, $subject);
                $sent++;
            } catch (\Throwable) {
                continue;
            }
        }

        $log->update(['sent_count' => $sent, 'status' => 'sent']);

        return $log->fresh();
    }
}
