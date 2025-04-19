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
        // Initialize configuration values from .env
        $this->apiUrl = env('SMS_API_URL', 'https://smsportal.hostpinnacle.co.ke/SMSApi/send');
        $this->apiKey = env('SMS_API_KEY');
        $this->userId = env('SMS_USER_ID');
        $this->password = env('SMS_PASSWORD');
        $this->senderId = env('SMS_SENDER_ID', 'ROYAL_KINGS'); // Default sender ID
    }

    public function sendSMS($phoneNumber, $message, $senderId = null)
    {
        // Use the provided sender ID or fallback to the default
        $senderId = $senderId ?? $this->senderId;

        // ✅ Log configuration values to check for issues
        Log::info('SMS Config Check', [
            'apiUrl' => $this->apiUrl,
            'apiKey' => $this->apiKey,
            'userId' => $this->userId,
            'password' => $this->password,
            'senderId' => $senderId,
        ]);

        // Build the raw POST data string
        $payload = "userid={$this->userId}"
            . "&password={$this->password}"
            . "&mobile={$phoneNumber}"
            . "&msg=" . urlencode($message)
            . "&senderid={$senderId}"
            . "&msgType=text"
            . "&duplicatecheck=true"
            . "&output=json"
            . "&sendMethod=quick";

        Log::info('Sending SMS to URL: ' . $this->apiUrl);
        Log::info('SMS Payload: ' . $payload);

        // Initialize cURL
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                "apikey: {$this->apiKey}",
                "cache-control: no-cache",
                "content-type: application/x-www-form-urlencoded",
            ],
        ]);

        // Execute the request
        $response = curl_exec($curl);
        $err = curl_error($curl);

        // Close the cURL session
        curl_close($curl);

        // Handle errors
        if ($err) {
            Log::error("SMS Sending Failed: $err");
            return ['status' => 'error', 'message' => "cURL Error #: $err"];
        }

        Log::info("SMS Response: $response");

        return json_decode($response, true);
    }
}
