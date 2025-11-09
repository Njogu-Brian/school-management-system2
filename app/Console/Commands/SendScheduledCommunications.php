<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledCommunication;
use App\Services\CommunicationService;
use App\Services\CommunicationRecipientService;
use Carbon\Carbon;

class SendScheduledCommunications extends Command
{
    protected $signature = 'communications:send-scheduled';
    protected $description = 'Send all due scheduled communications';

    protected $communicationService;

    public function __construct(
        CommunicationService $communicationService,
        protected CommunicationRecipientService $recipientService
    )
    {
        parent::__construct();
        $this->communicationService = $communicationService;
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

            $recipients = $this->recipientService->resolveDetailed([
                'target'       => $task->target,
                'classroom_id' => $task->classroom_id,
            ], $task->type);

            if ($recipients->isEmpty()) {
                $task->update(['status' => 'sent']);
                $this->warn("No recipients found for scheduled communication {$task->id} ({$task->target}).");
                continue;
            }

            $subject = $template->subject ?: 'School Notification';

            foreach ($recipients->chunk(200) as $chunk) {
                foreach ($chunk as $recipient) {
                    $message = replace_placeholders(
                        (string) $template->content,
                        $recipient['entity'],
                        $recipient['extra'] ?? []
                    );

                    if ($task->type === 'email') {
                        $this->communicationService->sendEmail(
                            $recipient['log_type'],
                            $recipient['entity_id'],
                            $recipient['contact'],
                            $subject,
                            $message,
                            $template->attachment,
                            [
                                'type'  => 'email',
                                'scope' => 'scheduled',
                                'title' => $subject,
                            ]
                        );
                    } else {
                        $this->communicationService->sendSMS(
                            $recipient['log_type'],
                            $recipient['entity_id'],
                            $recipient['contact'],
                            $message,
                            [
                                'type'  => 'sms',
                                'scope' => 'scheduled',
                                'title' => $template->title ?: 'SMS',
                            ]
                        );
                    }
                }
            }

            $task->update(['status' => 'sent']);
        }

        $this->info('All scheduled communications processed.');
    }
}
