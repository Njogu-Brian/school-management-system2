<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\User;

class StaffWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    public function __construct(User $user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function build()
    {
        $settings = \App\Models\Setting::whereIn('key', ['school_name'])->pluck('value', 'key');
        $schoolName = $settings['school_name'] ?? config('app.name', 'School');

        return $this->subject("Welcome to {$schoolName}")
                    ->view('emails.staff-welcome')
                    ->with([
                        'user' => $this->user,
                        'password' => $this->password,
                        'schoolName' => $schoolName,
                    ]);
    }
}
