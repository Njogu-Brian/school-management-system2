<?php

namespace App\Services\Website;

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Website\CampaignLog;
use App\Services\ParentSchoolNotificationService;
use Illuminate\Support\Facades\Log;

class FeeReminderService
{
    public function sendOverdueReminders(array $channels = ['sms', 'email']): int
    {
        $sent = 0;
        $parentNotify = app(ParentSchoolNotificationService::class);

        $overdueStudents = Student::query()
            ->where('archive', 0)
            ->whereHas('invoices', function ($q) {
                $q->whereNull('reversed_at')
                    ->where('balance', '>', 0)
                    ->whereDate('due_date', '<', now());
            })
            ->with(['invoices' => fn ($q) => $q->where('balance', '>', 0)->orderBy('due_date')])
            ->limit(100)
            ->get();

        foreach ($overdueStudents as $student) {
            $balance = round((float) $student->invoices->sum('balance'), 2);
            if ($balance <= 0) {
                continue;
            }

            $template = "Dear {{parent_name}}, a friendly reminder: {{student_name}} has an outstanding balance of KES {$balance}. Please pay via Parent Portal or M-Pesa. God bless — Royal Kings.";

            try {
                if (in_array('sms', $channels, true)) {
                    $parentNotify->sendSmsTemplateToStudentParents($student, $template, 'Fee Reminder', 'RKS_FINANCE');
                }
                if (in_array('email', $channels, true)) {
                    $parentNotify->sendEmailTemplateToStudentParents($student, 'Fee Reminder — Royal Kings', $template);
                }
                if (in_array('whatsapp', $channels, true)) {
                    $parentNotify->sendWhatsAppTemplateToStudentParents($student, $template, 'Fee Reminder');
                }
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Fee reminder failed', ['student_id' => $student->id, 'error' => $e->getMessage()]);
            }
        }

        CampaignLog::create([
            'campaign_name' => 'Overdue Fee Reminders',
            'type' => implode(',', $channels),
            'audience' => 'overdue_balances',
            'sent_count' => $sent,
            'status' => 'sent',
        ]);

        return $sent;
    }
}
