<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Generic in-app (database) message from portal/admin compose.
 */
class CustomAppMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected string $title,
        protected string $body,
        protected array $data = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return array_merge([
            'type' => 'custom_app_message',
            'category' => 'communication',
            'title' => $this->title,
            'body' => \Illuminate\Support\Str::limit($this->body, 500),
        ], $this->data);
    }
}
