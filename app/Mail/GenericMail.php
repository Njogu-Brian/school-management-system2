<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// App\Mail\GenericMail.php
class GenericMail extends Mailable
{
    public $subjectText;
    public $content;
    public $attachmentPath;

    public function __construct($subjectText, $content, $attachmentPath = null)
    {
        $this->subjectText = $subjectText;
        $this->content = $content;
        $this->attachmentPath = $attachmentPath;
    }

    public function build()
    {
        $mail = $this->subject($this->subjectText)
                    ->view('emails.generic')
                    ->with(['content' => $this->content]);

        if ($this->attachmentPath) {
            $fullPath = str_starts_with($this->attachmentPath, storage_path()) 
                ? $this->attachmentPath 
                : storage_path('app/public/' . $this->attachmentPath);
            
            if (file_exists($fullPath)) {
                $mail->attach($fullPath);
            }
        }

        return $mail;
    }
}
