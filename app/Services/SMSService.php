<?php

namespace App\Services;

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
            CURLOPT_POSTFIELDS => http_build_query([
                'userid' => $this->userId,
                'password' => $this->password,
                'mobile' => $phoneNumber,
                'msg' => $message,
                'senderid' => $senderId,
                'msgType' => 'text',
                'duplicatecheck' => 'true',
                'output' => 'json',
                'sendMethod' => 'quick',
            ]),
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
            return ['status' => 'error', 'message' => "cURL Error #: $err"];
        }

        // Return the API response
        return json_decode($response, true);
    }
}