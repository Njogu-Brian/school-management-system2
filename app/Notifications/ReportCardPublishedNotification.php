<?php

namespace App\Notifications;

use App\Models\Academics\ReportCard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReportCardPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected ReportCard $reportCard) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $student = $this->reportCard->student;
        $name = $student
            ? trim(($student->first_name ?? '').' '.($student->last_name ?? ''))
            : 'your child';

        return [
            'type' => 'report_card',
            'category' => 'academics',
            'title' => 'Report card ready',
            'body' => "A report form for {$name} has been published. Tap to view.",
            'report_card_id' => $this->reportCard->id,
            'student_id' => $student?->id,
            'deep_link' => $student
                ? "royalkingsusers://children/{$student->id}/report-cards/{$this->reportCard->id}"
                : null,
        ];
    }
}
