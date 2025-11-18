<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ExcelGeneratedNotification extends Notification
{
    use Queueable;

    protected $fileUrl;
    protected $filename;

    /**
     * Create a new notification instance.
     */
    public function __construct($fileUrl, $filename)
    {
        $this->fileUrl = $fileUrl;
        $this->filename = $filename;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Excel Export Generated')
            ->line('Your Excel export has been generated successfully.')
            ->line('Filename: ' . $this->filename)
            ->action('Download Excel', $this->fileUrl)
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'message' => 'Excel export generated successfully: ' . $this->filename,
            'file_url' => $this->fileUrl,
            'filename' => $this->filename,
            'type' => 'excel_export',
        ];
    }
}

