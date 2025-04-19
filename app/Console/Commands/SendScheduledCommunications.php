<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledCommunication;
use App\Models\Student;
use App\Models\Staff;
use App\Models\ParentInfo;
use App\Services\CommunicationService;
use Carbon\Carbon;

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
        $due = ScheduledCommunication::where('status', 'pending')
            ->where('send_at', '<=', Carbon::now())
            ->get();

        foreach ($due as $task) {
            $template = $task->template;

            $targets = match ($task->target) {
                'students' => Student::when($task->classroom_id, fn($q) => $q->where('classroom_id', $task->classroom_id))->get(),
                'parents' => ParentInfo::all(),
                'staff' => Staff::all(),
                'teachers' => Staff::where('designation', 'like', '%teacher%')->get(),
            };

            foreach ($targets as $recipient) {
                $message = str_replace(
                    ['{{ student_name }}', '{{ parent_name }}', '{{ admission_number }}'],
                    [$recipient->getFullNameAttribute() ?? '', $recipient->name ?? '', $recipient->admission_number ?? ''],
                    $template->content
                );

                if ($task->type === 'email' && !empty($recipient->email)) {
                    $this->commService->sendEmail($task->target, $recipient->id, $recipient->email, $template->subject, 'emails.generic', ['content' => $message]);
                }

                if ($task->type === 'sms' && !empty($recipient->phone)) {
                    $this->commService->sendSMS($task->target, $recipient->id, $recipient->phone, $message);
                }
            }

            $task->update(['status' => 'sent']);
        }

        $this->info('All scheduled communications processed.');
    }
}
