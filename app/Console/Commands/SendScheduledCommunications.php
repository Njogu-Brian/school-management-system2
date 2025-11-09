<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledCommunication;
use App\Models\Student;
use App\Models\Staff;
use App\Models\ParentInfo;
use App\Services\CommunicationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SendScheduledCommunications extends Command
{
    protected $signature = 'communications:send-scheduled';
    protected $description = 'Send all due scheduled communications';

    protected $commService;

    public function __construct(CommunicationService $commService)
    {
        parent::__construct();
        $this->commService = $commService;
    }

    public function handle()
    {
        $due = ScheduledCommunication::with('template')
            ->where('status', 'pending')
            ->where('send_at', '<=', Carbon::now())
            ->get();

        foreach ($due as $task) {
            $template = $task->template;

            if (!$template) {
                $task->update(['status' => 'sent']);
                $this->warn("Skipped scheduled communication {$task->id}: missing template.");
                continue;
            }

            $recipients = $this->resolveRecipients($task);
            if ($recipients->isEmpty()) {
                $task->update(['status' => 'sent']);
                $this->warn("No recipients found for scheduled communication {$task->id} ({$task->target}).");
                continue;
            }

            $subject = $template->subject ?: 'School Notification';

            foreach ($recipients as $recipient) {
                $message = replace_placeholders(
                    (string) $template->content,
                    $recipient['entity'],
                    $recipient['extra'] ?? []
                );

                if ($task->type === 'email') {
                    $this->commService->sendEmail(
                        $recipient['log_type'],
                        $recipient['entity_id'],
                        $recipient['contact'],
                        $subject,
                        $message,
                        $template->attachment
                    );
                } else {
                    $this->commService->sendSMS(
                        $recipient['log_type'],
                        $recipient['entity_id'],
                        $recipient['contact'],
                        $message
                    );
                }
            }

            $task->update(['status' => 'sent']);
        }

        $this->info('All scheduled communications processed.');
    }

    protected function resolveRecipients(ScheduledCommunication $task): Collection
    {
        return match ($task->target) {
            'students' => $this->recipientsForStudents($task),
            'parents'  => $this->recipientsForParents($task),
            'staff'    => $this->recipientsForStaff($task),
            'teachers' => $this->recipientsForTeachers($task),
            default    => collect(),
        };
    }

    protected function recipientsForStudents(ScheduledCommunication $task): Collection
    {
        $students = Student::with('parent', 'classroom', 'stream')
            ->when($task->classroom_id, fn($q) => $q->where('classroom_id', $task->classroom_id))
            ->get();

        return $students->flatMap(function (Student $student) use ($task) {
            $contacts = $this->preferredStudentContacts($student, $task->type);

            return $contacts->map(fn($contact) => [
                'contact'   => $contact['value'],
                'entity'    => $contact['entity'],
                'entity_id' => $contact['entity_id'],
                'log_type'  => $contact['log_type'],
                'extra'     => $contact['extra'] ?? [],
            ]);
        })->unique('contact');
    }

    protected function recipientsForParents(ScheduledCommunication $task): Collection
    {
        $parents = ParentInfo::with('students.classroom', 'students.stream')->get();

        return $parents->flatMap(function (ParentInfo $parent) use ($task) {
            $contacts = collect([
                ['value' => $parent->father_email,   'label' => $parent->father_name,   'type' => 'email'],
                ['value' => $parent->mother_email,   'label' => $parent->mother_name,   'type' => 'email'],
                ['value' => $parent->guardian_email, 'label' => $parent->guardian_name, 'type' => 'email'],
                ['value' => $parent->father_phone,   'label' => $parent->father_name,   'type' => 'sms'],
                ['value' => $parent->mother_phone,   'label' => $parent->mother_name,   'type' => 'sms'],
                ['value' => $parent->guardian_phone, 'label' => $parent->guardian_name, 'type' => 'sms'],
            ]);

            return $contacts
                ->filter(fn($entry) => filled($entry['value']) && $entry['type'] === $task->type)
                ->map(fn($entry) => [
                    'contact'   => $entry['value'],
                    'entity'    => $parent,
                    'entity_id' => $parent->id,
                    'log_type'  => 'parent',
                    'extra'     => [
                        '{parent_name}' => $entry['label'] ?? '',
                    ],
                ]);
        })->unique('contact');
    }

    protected function recipientsForStaff(ScheduledCommunication $task): Collection
    {
        return Staff::with('user')->get()->map(function (Staff $staff) use ($task) {
            $contact = $task->type === 'email'
                ? ($staff->work_email ?? $staff->personal_email ?? $staff->email ?? optional($staff->user)->email)
                : $staff->phone_number;

            if (!filled($contact)) {
                return null;
            }

            return [
                'contact'   => $contact,
                'entity'    => $staff,
                'entity_id' => $staff->id,
                'log_type'  => 'staff',
            ];
        })->filter()->unique('contact');
    }

    protected function recipientsForTeachers(ScheduledCommunication $task): Collection
    {
        $teachers = Staff::with(['user.roles'])
            ->whereHas('user.roles', fn($q) => $q->where('name', 'Teacher'))
            ->get();

        return $teachers->map(function (Staff $staff) use ($task) {
            $contact = $task->type === 'email'
                ? ($staff->work_email ?? optional($staff->user)->email)
                : $staff->phone_number;

            if (!filled($contact)) {
                return null;
            }

            return [
                'contact'   => $contact,
                'entity'    => $staff,
                'entity_id' => $staff->id,
                'log_type'  => 'staff',
            ];
        })->filter()->unique('contact');
    }

    protected function preferredStudentContacts(Student $student, string $channel): Collection
    {
        $contacts = collect();

        if ($channel === 'email') {
            if (filled($student->email ?? null)) {
                $contacts->push([
                    'value'     => $student->email,
                    'entity'    => $student,
                    'entity_id' => $student->id,
                    'log_type'  => 'student',
                ]);
            }

            if ($student->parent) {
                foreach (['father_email', 'mother_email', 'guardian_email'] as $field) {
                    if (filled($student->parent->{$field})) {
                        $contacts->push([
                            'value'     => $student->parent->{$field},
                            'entity'    => $student,
                            'entity_id' => $student->parent->id,
                            'log_type'  => 'parent',
                            'extra'     => [
                                '{parent_name}' => optional($student->parent)->{$field === 'father_email'
                                    ? 'father_name'
                                    : ($field === 'mother_email' ? 'mother_name' : 'guardian_name')} ?? '',
                            ],
                        ]);
                    }
                }
            }
        } else {
            if (filled($student->phone_number ?? null)) {
                $contacts->push([
                    'value'     => $student->phone_number,
                    'entity'    => $student,
                    'entity_id' => $student->id,
                    'log_type'  => 'student',
                ]);
            }

            if ($student->parent) {
                foreach (['father_phone', 'mother_phone', 'guardian_phone'] as $field) {
                    if (filled($student->parent->{$field})) {
                        $contacts->push([
                            'value'     => $student->parent->{$field},
                            'entity'    => $student,
                            'entity_id' => $student->parent->id,
                            'log_type'  => 'parent',
                            'extra'     => [
                                '{parent_name}' => optional($student->parent)->{$field === 'father_phone'
                                    ? 'father_name'
                                    : ($field === 'mother_phone' ? 'mother_name' : 'guardian_name')} ?? '',
                            ],
                        ]);
                    }
                }
            }
        }

        return $contacts;
    }
}
