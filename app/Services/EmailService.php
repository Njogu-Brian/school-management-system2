<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    public function send($to, $subject, $view, $data = [])
    {
        try {
            Mail::send($view, $data, function ($message) use ($to, $subject) {
                $message->to($to)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("📧 Email sent to: {$to}");
            return ['status' => 'success', 'message' => 'Email sent'];
        } catch (\Exception $e) {
            Log::error("❌ Email failed to {$to}: " . $e->getMessage());
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }
}
