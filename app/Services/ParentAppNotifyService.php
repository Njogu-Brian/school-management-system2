<?php

namespace App\Services;

use App\Models\Academics\ReportCard;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\User;
use App\Notifications\InvoiceGeneratedNotification;
use App\Notifications\ReportCardPublishedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * In-app + Expo push notifications for parent users (Users app).
 */
class ParentAppNotifyService
{
    public function __construct(protected ExpoPushService $push) {}

    public function notifyReportCardPublished(ReportCard $reportCard): void
    {
        $reportCard->loadMissing('student');
        $student = $reportCard->student;
        if (! $student) {
            return;
        }

        $users = $this->parentUsersForStudent($student);
        if ($users->isEmpty()) {
            return;
        }

        try {
            Notification::send($users, new ReportCardPublishedNotification($reportCard));
        } catch (\Throwable $e) {
            Log::warning('Report card in-app notify failed: '.$e->getMessage(), [
                'report_card_id' => $reportCard->id,
            ]);
        }

        $name = trim(($student->first_name ?? '').' '.($student->last_name ?? '')) ?: 'your child';
        $this->pushToUsers(
            $users,
            'Report card ready',
            "A report form for {$name} has been published.",
            [
                'type' => 'report_card',
                'report_card_id' => $reportCard->id,
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/report-cards/{$reportCard->id}",
            ]
        );
    }

    public function notifyInvoiceGenerated(Invoice $invoice): void
    {
        $invoice->loadMissing('student');
        $student = $invoice->student;
        if (! $student) {
            return;
        }

        $users = $this->parentUsersForStudent($student);
        if ($users->isEmpty()) {
            return;
        }

        try {
            Notification::send($users, new InvoiceGeneratedNotification($invoice));
        } catch (\Throwable $e) {
            Log::warning('Invoice in-app notify failed: '.$e->getMessage(), [
                'invoice_id' => $invoice->id,
            ]);
        }

        $name = trim(($student->first_name ?? '').' '.($student->last_name ?? '')) ?: 'your child';
        $number = $invoice->invoice_number ?: ('#'.$invoice->id);
        $this->pushToUsers(
            $users,
            'New fee invoice',
            "Invoice {$number} for {$name} is ready to view.",
            [
                'type' => 'invoice',
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/invoices/{$invoice->id}",
            ]
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @param  array<string, mixed>  $data
     */
    protected function pushToUsers($users, string $title, string $body, array $data): void
    {
        $userIds = $users->pluck('id')->all();
        if ($userIds === []) {
            return;
        }

        $tokens = DB::table('user_device_tokens')
            ->whereIn('user_id', $userIds)
            ->pluck('token')
            ->filter(fn ($t) => is_string($t) && $t !== '')
            ->values()
            ->all();

        if ($tokens === []) {
            return;
        }

        $this->push->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function parentUsersForStudent(Student $student)
    {
        if (! $student->parent_id) {
            return collect();
        }

        return User::query()
            ->where('parent_id', $student->parent_id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Parent', 'Guardian']))
            ->get();
    }
}
