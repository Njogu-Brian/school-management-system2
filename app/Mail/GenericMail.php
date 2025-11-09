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
            $path = $this->attachmentPath;

            if (!str_starts_with($path, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:\\\\/', $path)) {
                $path = storage_path('app/public/' . ltrim($path, '/'));
            }

            if (is_file($path)) {
                $mail->attach($path);
            }
        }

        return $mail;
    }
}
