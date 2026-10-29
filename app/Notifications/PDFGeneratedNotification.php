<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PDFGeneratedNotification extends Notification
{
    use Queueable;

    protected $url;
    protected $filename;

    /**
     * Create a new notification instance.
     */
    public function __construct($url, $filename)
    {
        $this->url = $url;
        $this->filename = $filename;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $isZip = str_ends_with(strtolower((string) $this->filename), '.zip');

        return (new MailMessage)
            ->subject(($isZip ? 'Invoice PDF batches ready: ' : 'PDF Generated: ').$this->filename)
            ->line($isZip
                ? 'Your invoice PDF export is ready. The ZIP contains one PDF per class/stream batch (alphabetical within each), plus a merged PDF when available.'
                : 'Your PDF document has been generated successfully.')
            ->action($isZip ? 'Download ZIP' : 'Download PDF', $this->url)
            ->line('Thank you for using our system!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'message' => 'PDF ready: '.$this->filename,
            'url' => $this->url,
            'filename' => $this->filename,
        ];
    }
}

