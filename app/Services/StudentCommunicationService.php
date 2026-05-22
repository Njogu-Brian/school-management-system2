<?php

namespace App\Services;

use App\Models\CommunicationLog;
use App\Models\FeeReminder;
use App\Models\ScheduledFeeCommunication;
use App\Models\Student;
use Illuminate\Support\Collection;

class StudentCommunicationService
{
    /**
     * Successfully sent (or delivered) communications for a student.
     */
    public function sentHistoryForStudent(Student $student, int $limit = 50): Collection
    {
        $studentId = (int) $student->id;

        return CommunicationLog::query()
            ->with('payment:id,student_id,receipt_number')
            ->where(function ($q) use ($studentId) {
                $q->where(function ($q2) use ($studentId) {
                    $q2->where('recipient_id', $studentId)
                        ->whereIn('recipient_type', ['student', 'parent', 'class', 'all', 'one_parent', 'specific_students']);
                })->orWhereHas('payment', fn ($p) => $p->where('student_id', $studentId));
            })
            ->whereIn('status', ['sent', 'delivered', 'success'])
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Pending / paused outbound items targeting this student.
     */
    public function upcomingForStudent(Student $student): array
    {
        $studentId = (int) $student->id;

        $feeReminders = FeeReminder::query()
            ->where('student_id', $studentId)
            ->whereIn('status', ['pending', 'paused'])
            ->orderBy('due_date')
            ->get();

        $scheduledFee = ScheduledFeeCommunication::query()
            ->with('template:id,title,code')
            ->whereIn('status', ['pending', 'active', 'paused'])
            ->where(function ($q) use ($studentId, $student) {
                $q->where('student_id', $studentId)
                    ->orWhere('target', 'all')
                    ->orWhere(function ($q2) use ($studentId) {
                        $q2->where('target', 'specific_students')
                            ->whereJsonContains('selected_student_ids', $studentId);
                    });
                if ($student->classroom_id) {
                    $q->orWhere(function ($q3) use ($student) {
                        $q3->where('target', 'class')
                            ->whereJsonContains('classroom_ids', (int) $student->classroom_id);
                    });
                }
            })
            ->orderByRaw('COALESCE(recurrence_next_at, send_at) ASC')
            ->limit(30)
            ->get()
            ->filter(function (ScheduledFeeCommunication $item) use ($student) {
                if ($item->target === 'all' || $item->target === 'class') {
                    return true;
                }
                if ($item->target === 'specific_students') {
                    return in_array($student->id, $item->selected_student_ids ?? [], true);
                }
                if ($item->target === 'one_parent') {
                    return (int) $item->student_id === (int) $student->id;
                }

                return true;
            })
            ->values();

        return [
            'fee_reminders' => $feeReminders,
            'scheduled_fee_communications' => $scheduledFee,
            'communications_paused' => CommunicationPauseService::isPaused(),
            'pause_meta' => CommunicationPauseService::getMeta(),
        ];
    }
}
