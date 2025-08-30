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

        // Build payload
        $payload = http_build_query([
            'userid'        => $this->userId,
            'password'      => $this->password,
            'mobile'        => $phoneNumber,
            'msg'           => $message,
            'senderid'      => $senderId,
            'msgType'       => 'text',
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
}
