<?php

namespace App\Services;

use App\Models\Academics\Homework;
use App\Models\Academics\ReportCard;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CustomAppMessageNotification;
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

        $name = $this->childName($student);
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

        $name = $this->childName($student);
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

    public function notifyInvoiceAdjusted(Student $student, string $summary): void
    {
        $name = $this->childName($student);
        $this->notifyParentsOfStudent(
            $student,
            'Fee invoice updated',
            "An adjustment was made on {$name}'s fees. {$summary}",
            [
                'type' => 'invoice_adjustment',
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/fees",
            ]
        );
    }

    public function notifyPaymentReceived(Payment $payment): void
    {
        if ($payment->reversed) {
            return;
        }
        $receipt = (string) ($payment->receipt_number ?? '');
        if (str_starts_with($receipt, 'SWIM-')) {
            return;
        }
        if (($payment->payment_channel ?? '') === 'balance_brought_forward') {
            return;
        }

        $student = $payment->student ?? Student::find($payment->student_id);
        if (! $student) {
            return;
        }

        $amount = number_format((float) $payment->amount, 2);
        $name = $this->childName($student);
        $this->notifyParentsOfStudent(
            $student,
            'Payment received',
            "We received Ksh {$amount} for {$name}.",
            [
                'type' => 'payment',
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/payments/{$payment->id}",
            ]
        );
    }

    public function notifyChildAbsent(Student $student, ?string $date = null): void
    {
        $name = $this->childName($student);
        $when = $date ?: 'today';
        $this->notifyParentsOfStudent(
            $student,
            'Marked absent',
            "{$name} was marked absent {$when}.",
            [
                'type' => 'attendance_absent',
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/attendance",
            ]
        );
    }

    public function notifyDiaryComment(Student $student, string $preview, ?int $exceptUserId = null): void
    {
        $name = $this->childName($student);
        $this->notifyParentsOfStudent(
            $student,
            'New diary message',
            "{$name}: ".$preview,
            [
                'type' => 'diary',
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/diary",
            ],
            $exceptUserId
        );
    }

    public function notifyHomeworkIssued(Homework $homework): void
    {
        if (! $homework->classroom_id) {
            return;
        }

        $query = Student::query()
            ->where('classroom_id', $homework->classroom_id)
            ->where('archive', 0)
            ->where('is_alumni', false);
        if ($homework->stream_id) {
            $query->where('stream_id', $homework->stream_id);
        }

        $users = collect();
        foreach ($query->get() as $student) {
            $users = $users->merge($this->parentUsersForStudent($student));
        }
        $users = $users->unique('id')->values();
        if ($users->isEmpty()) {
            return;
        }

        $subject = $homework->subject?->name ? $homework->subject->name.': ' : '';
        $title = 'New homework';
        $body = $subject.($homework->title ?: 'Homework has been issued.');

        $this->notifyUsers(
            $users,
            $title,
            $body,
            [
                'type' => 'homework',
                'homework_id' => $homework->id,
                'classroom_id' => $homework->classroom_id,
            ]
        );
    }

    public function notifyTransportChanged(Student $student): void
    {
        $name = $this->childName($student);
        $this->notifyParentsOfStudent(
            $student,
            'Transport updated',
            "Transport details for {$name} have changed.",
            [
                'type' => 'transport',
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/transport",
            ]
        );
    }

    public function notifyActivityChangeSubmitted(Student $student, string $activityName, string $action, int $year, int $term): void
    {
        $name = $this->childName($student);
        $verb = $action === 'leave' ? 'leave' : 'join';
        $this->notifyParentsOfStudent(
            $student,
            'Activity change sent',
            "The school office has been notified that {$name} should {$verb} {$activityName} for Term {$term} {$year}. They will confirm the change.",
            [
                'type' => 'co_curricular',
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/co-curricular",
            ]
        );
    }

    public function notifyActivityChangeReviewed(
        Student $student,
        string $activityName,
        string $action,
        bool $approved,
        int $year,
        int $term,
    ): void {
        $name = $this->childName($student);
        $verb = $action === 'leave' ? 'leave' : 'join';
        if ($approved) {
            $title = 'Activity change confirmed';
            $body = "The school confirmed that {$name} will {$verb} {$activityName} for Term {$term} {$year}.";
        } else {
            $title = 'Activity change not confirmed';
            $body = "The school did not confirm the request for {$name} to {$verb} {$activityName} for Term {$term} {$year}.";
        }
        $this->notifyParentsOfStudent(
            $student,
            $title,
            $body,
            [
                'type' => 'co_curricular',
                'student_id' => $student->id,
                'deep_link' => "royalkingsusers://children/{$student->id}/co-curricular",
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyParentsOfStudent(
        Student $student,
        string $title,
        string $body,
        array $data = [],
        ?int $exceptUserId = null,
    ): void {
        $users = $this->parentUsersForStudent($student);
        if ($exceptUserId) {
            $users = $users->reject(fn (User $u) => (int) $u->id === $exceptUserId)->values();
        }
        $this->notifyUsers($users, $title, $body, $data);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, User>  $users
     * @param  array<string, mixed>  $data
     */
    public function notifyUsers($users, string $title, string $body, array $data = []): void
    {
        $users = $users->filter(fn ($u) => $u instanceof User)->unique('id')->values();
        if ($users->isEmpty()) {
            return;
        }

        try {
            Notification::send($users, new CustomAppMessageNotification($title, $body, $data));
        } catch (\Throwable $e) {
            Log::warning('Parent in-app notify failed: '.$e->getMessage(), ['title' => $title]);
        }

        $this->pushToUsers($users, $title, $body, $data);
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
            ->get();
    }

    protected function childName(Student $student): string
    {
        return trim(($student->first_name ?? '').' '.($student->last_name ?? '')) ?: 'your child';
    }
}
