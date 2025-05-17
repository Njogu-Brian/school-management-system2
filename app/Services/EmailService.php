<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\GenericMail;

class EmailService
{
    public function send($to, $subject, $viewOrHtml, $data = [])
    {
        try {
            // If you're using raw HTML content (from template)
            $content = view($viewOrHtml, $data)->render();

            Mail::to($to)->send(new GenericMail($subject, $content));

            Log::info("📧 Email sent to: {$to}");
            return ['status' => 'success', 'message' => 'Email sent'];
        } catch (\Exception $e) {
            Log::error("❌ Email failed to {$to}: " . $e->getMessage());
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }
}

