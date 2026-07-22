<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AnnouncementPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Announcement $announcement) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $body = trim(strip_tags((string) $this->announcement->content));

        return [
            'type' => 'announcement',
            'category' => 'announcements',
            'title' => $this->announcement->title,
            'body' => \Illuminate\Support\Str::limit($body !== '' ? $body : 'New announcement published', 180),
            'announcement_id' => $this->announcement->id,
            'url' => url('/communication/announcements'),
        ];
    }
}
