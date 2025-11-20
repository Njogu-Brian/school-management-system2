<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SMSService
{
    protected $apiUrl;
    protected $apiKey;
    protected $userId;
    protected $password;
    protected $senderId;

    public function __construct()
    {
        $this->apiUrl   = env('SMS_API_URL', 'https://smsportal.hostpinnacle.co.ke/SMSApi/send');
        $this->apiKey   = env('SMS_API_KEY');
        $this->userId   = env('SMS_USER_ID');
        $this->password = env('SMS_PASSWORD');
        $this->senderId = env('SMS_SENDER_ID', 'ROYAL_KINGS');
    }

    public function sendSMS($phoneNumber, $message, $senderId = null)
    {
        // Normalize number: remove leading + if present
        $phoneNumber = preg_replace('/^\+/', '', $phoneNumber);

        $senderId = $senderId ?? $this->senderId;

        // Detect if message contains Unicode characters
        // GSM 7-bit charset includes: A-Z, a-z, 0-9, and some special chars
        // If message contains characters outside this set, use Unicode
        $msgType = $this->detectMessageType($message);

        // Build payload
        $payload = http_build_query([
            'userid'        => $this->userId,
            'password'      => $this->password,
            'mobile'        => $phoneNumber,
            'msg'           => $message,
            'senderid'      => $senderId,
            'msgType'       => $msgType,
            'duplicatecheck'=> 'true',
            'output'        => 'json',
            'sendMethod'    => 'quick',
        ]);

        Log::info('SMS Sending Started', [
            'url'     => $this->apiUrl,
            'phone'   => $phoneNumber,
            'sender'  => $senderId,
            'message' => $message,
        ]);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                "apikey: {$this->apiKey}",
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            ],
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($err) {
            Log::error("SMS Sending Failed: $err");
            return ['status' => 'error', 'message' => "cURL Error: $err"];
        }

        Log::info("Raw SMS Response: $response");

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning("Invalid JSON response from SMS provider, returning raw.");
            return ['status' => 'unknown', 'raw' => $response];
        }

        return $decoded;
    }

    /**
     * Detect if message requires Unicode encoding
     * Returns 'unicode' if message contains non-ASCII characters, 'text' otherwise
     * 
     * Some SMS providers (like HostPinnacle) are strict about message types.
     * If the message contains any character outside the ASCII range (0-127), 
     * we must use Unicode encoding to avoid "Msg Text and MsgType Mismatch" errors.
     */
    protected function detectMessageType($message): string
    {
        // Simple check: if string length in bytes differs from character count,
        // it contains multi-byte UTF-8 characters (requires Unicode)
        if (mb_strlen($message, 'UTF-8') !== strlen($message)) {
            Log::info('SMS message contains multi-byte UTF-8 characters, using unicode msgType');
            return 'unicode';
        }

        // Check each byte to ensure all are within ASCII range (0-127)
        $length = strlen($message);
        for ($i = 0; $i < $length; $i++) {
            $byte = ord($message[$i]);
            // If any byte is outside ASCII range (0-127), message requires Unicode
            if ($byte > 127) {
                Log::info('SMS message contains non-ASCII byte, using unicode msgType', [
                    'byte' => $byte,
                    'position' => $i,
                    'char' => $message[$i] ?? 'N/A'
                ]);
                return 'unicode';
            }
        }

        // All characters are within ASCII range (0-127), safe to use text
        return 'text';
    }
}
