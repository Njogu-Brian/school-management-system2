<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SMSService
{
    protected $apiUrl;
    protected $apiKey;
    protected $userId;
    protected $password;
    protected $senderId;
    protected $financeSenderId;
    protected $balanceUrl;

    public function __construct()
    {
        $this->apiUrl   = env('SMS_API_URL', 'https://smsportal.hostpinnacle.co.ke/SMSApi/send');
        $this->apiKey   = env('SMS_API_KEY');
        $this->userId   = env('SMS_USER_ID');
        $this->password = env('SMS_PASSWORD');
        $this->senderId = env('SMS_SENDER_ID', 'ROYAL_KINGS');
        $this->financeSenderId = env('SMS_SENDER_ID_FINANCE', 'RKS_FINANCE');
        // HostPinnacle API base URL
        $apiBase = 'https://smsportal.hostpinnacle.co.ke/SMSApi';
        // Use "Read Account Status" API for balance check (from HostPinnacle documentation)
        $this->balanceUrl = env('SMS_BALANCE_URL', $apiBase . '/readAccountStatus');
    }

    /**
     * Check SMS account balance
     * Returns balance as float, or null if unable to check
     * @param bool $forceRefresh Force a fresh check (bypass cache)
     */
    public function checkBalance(bool $forceRefresh = false): ?float
    {
        $cacheKey = 'sms_balance';
        
        // If forcing refresh, clear cache first
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        // Cache balance for 5 minutes to avoid excessive API calls
        return Cache::remember($cacheKey, 300, function () {
            // Use the new getAccountStatus method which uses the correct HostPinnacle API
            $accountStatus = $this->getAccountStatus();
            
            if ($accountStatus) {
                // Try different possible response formats for balance
                $balance = data_get($accountStatus, 'balance') 
                    ?? data_get($accountStatus, 'credits') 
                    ?? data_get($accountStatus, 'sms_balance')
                    ?? data_get($accountStatus, 'smsBalance')
                    ?? data_get($accountStatus, 'data.balance')
                    ?? data_get($accountStatus, 'data.credits')
                    ?? data_get($accountStatus, 'result.balance')
                    ?? data_get($accountStatus, 'result.credits')
                    ?? null;
                
                if ($balance !== null) {
                    $balance = (float) $balance;
                    Log::info("SMS Balance Retrieved from Account Status", [
                        'balance' => $balance
                    ]);
                    return $balance;
                }
            }
            
            // Fallback to old method if getAccountStatus doesn't work
            $apiBase = 'https://smsportal.hostpinnacle.co.ke/SMSApi';
            $possibleEndpoints = [
                $this->balanceUrl, // Primary endpoint from config
                $apiBase . '/readAccountStatus', // HostPinnacle documented endpoint
                $apiBase . '/balance', // Fallback
                $apiBase . '/getBalance', // Fallback
            ];

            foreach ($possibleEndpoints as $endpoint) {
                try {
                    $payload = http_build_query([
                        'userid'   => $this->userId,
                        'password' => $this->password,
                        'output'   => 'json',
                    ]);

                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL            => $endpoint,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT        => 10,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => $payload,
                        CURLOPT_HTTPHEADER     => [
                            "apikey: {$this->apiKey}",
                            "cache-control: no-cache",
                            "content-type: application/x-www-form-urlencoded",
                        ],
                    ]);

                    $response = curl_exec($curl);
                    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    $err      = curl_error($curl);
                    curl_close($curl);

                    if ($err || $httpCode !== 200) {
                        Log::debug("SMS Balance Check Failed for endpoint: $endpoint", [
                            'http_code' => $httpCode,
                            'error' => $err
                        ]);
                        continue; // Try next endpoint
                    }

                    $decoded = json_decode($response, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::debug("Invalid JSON response from SMS balance API: $endpoint", [
                            'raw_response' => $response,
                            'json_error' => json_last_error_msg()
                        ]);
                        continue; // Try next endpoint
                    }

                    // Try different possible response formats
                    // HostPinnacle might return balance in various formats
                    $balance = data_get($decoded, 'balance') 
                        ?? data_get($decoded, 'credits') 
                        ?? data_get($decoded, 'sms_balance')
                        ?? data_get($decoded, 'smsBalance')
                        ?? data_get($decoded, 'data.balance')
                        ?? data_get($decoded, 'data.credits')
                        ?? data_get($decoded, 'result.balance')
                        ?? data_get($decoded, 'result.credits')
                        ?? data_get($decoded, 'response.balance')
                        ?? data_get($decoded, 'response.credits')
                        ?? null;
                    
                    // Also check if response contains balance as string that needs parsing
                    if ($balance === null && isset($decoded['message'])) {
                        // Sometimes balance is in a message like "Your balance is 100"
                        if (preg_match('/balance[:\s]+([\d.]+)/i', $decoded['message'], $matches)) {
                            $balance = (float) $matches[1];
                        }
                    }

                    if ($balance !== null) {
                        $balance = (float) $balance;
                        Log::info("SMS Balance Checked Successfully", [
                            'balance' => $balance,
                            'endpoint' => $endpoint,
                            'response' => $decoded
                        ]);
                        return $balance;
                    }

                    // If balance not found but response looks valid, log full response for debugging
                    // This helps identify the correct response format
                    Log::info("SMS Balance API responded but balance not found in expected format", [
                        'endpoint' => $endpoint,
                        'full_response' => $decoded,
                        'response_keys' => array_keys($decoded ?? [])
                    ]);
                } catch (\Exception $e) {
                    Log::debug("Exception checking SMS balance at $endpoint: " . $e->getMessage());
                    continue; // Try next endpoint
                }
            }

            // If all endpoints failed, log warning
            Log::warning("Unable to check SMS balance from any endpoint");
            return null;
        });
    }

    /**
     * Clear the cached balance to force a fresh check
     */
    public function clearBalanceCache(): void
    {
        Cache::forget('sms_balance');
    }

    /**
     * Check if sufficient credits are available
     * @param int $required Minimum credits required (default 1)
     * @return bool
     */
    public function hasSufficientCredits(int $required = 1): bool
    {
        $balance = $this->checkBalance();
        
        if ($balance === null) {
            // If we can't check balance, allow sending but log warning
            Log::warning("Unable to check SMS balance, proceeding with send attempt");
            return true; // Don't block if we can't check
        }

        return $balance >= $required;
    }

    public function sendSMS($phoneNumber, $message, $senderId = null)
    {
        // Check balance before sending
        if (!$this->hasSufficientCredits(1)) {
            $balance = $this->checkBalance();
            Log::error("SMS sending blocked: Insufficient credits", [
                'phone' => $phoneNumber,
                'balance' => $balance,
                'required' => 1
            ]);
            
            // Clear cache to force fresh check next time
            Cache::forget('sms_balance');
            
            return [
                'status' => 'error',
                'message' => 'Insufficient SMS credits',
                'balance' => $balance,
                'error_code' => 'INSUFFICIENT_CREDITS'
            ];
        }

        // Normalize number: remove leading + if present
        $phoneNumber = preg_replace('/^\+/', '', $phoneNumber);

        $senderId = $senderId ?? $this->senderId;

        // Detect if message contains Unicode characters
        // GSM 7-bit charset includes: A-Z, a-z, 0-9, and some special chars
        // If message contains characters outside this set, use Unicode
        $msgType = $this->detectMessageType($message);

        // Build payload
        // Note: duplicatecheck set to 'false' temporarily to avoid blocking legitimate messages
        $payload = http_build_query([
            'userid'        => $this->userId,
            'password'      => $this->password,
            'mobile'        => $phoneNumber,
            'msg'           => $message,
            'senderid'      => $senderId,
            'msgType'       => $msgType,
            'duplicatecheck'=> 'false', // Changed to false - if messages are being blocked, this might help
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
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err      = curl_error($curl);
        curl_close($curl);

        if ($err) {
            Log::error("SMS Sending Failed: $err", [
                'phone' => $phoneNumber,
                'http_code' => $httpCode
            ]);
            return ['status' => 'error', 'message' => "cURL Error: $err"];
        }

        Log::info("Raw SMS Response: $response", [
            'phone' => $phoneNumber,
            'http_code' => $httpCode
        ]);

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning("Invalid JSON response from SMS provider, returning raw.", [
                'phone' => $phoneNumber,
                'raw_response' => $response,
                'json_error' => json_last_error_msg()
            ]);
            return ['status' => 'unknown', 'raw' => $response];
        }

        // Log warning if msgId is empty - this might indicate the message wasn't queued
        if (empty($decoded['msgId']) && isset($decoded['status']) && $decoded['status'] === 'success') {
            Log::warning("SMS provider returned success but msgId is empty - message may not be queued for delivery", [
                'phone' => $phoneNumber,
                'transaction_id' => $decoded['transactionId'] ?? null,
                'response' => $decoded
            ]);
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

    /**
     * Check delivery status by transaction ID using HostPinnacle's "Check MIS by Transaction ID" API
     */
    public function checkDeliveryStatus(string $transactionId): ?array
    {
        try {
            $apiBase = 'https://smsportal.hostpinnacle.co.ke/SMSApi';
            $endpoint = $apiBase . '/checkMIS';
            
            $payload = http_build_query([
                'userid'        => $this->userId,
                'password'      => $this->password,
                'transactionId' => $transactionId,
                'output'        => 'json',
            ]);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    "apikey: {$this->apiKey}",
                    "cache-control: no-cache",
                    "content-type: application/x-www-form-urlencoded",
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err      = curl_error($curl);
            curl_close($curl);

            if ($err || $httpCode !== 200) {
                Log::warning("Delivery status check failed", [
                    'transaction_id' => $transactionId,
                    'http_code' => $httpCode,
                    'error' => $err
                ]);
                return null;
            }

            $decoded = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Invalid JSON response from delivery status API", [
                    'transaction_id' => $transactionId,
                    'raw_response' => $response
                ]);
                return null;
            }

            Log::info("Delivery status checked", [
                'transaction_id' => $transactionId,
                'response' => $decoded
            ]);

            return $decoded;
        } catch (\Exception $e) {
            Log::error("Exception checking delivery status: " . $e->getMessage(), [
                'transaction_id' => $transactionId
            ]);
            return null;
        }
    }

    /**
     * Get account status (includes balance) using HostPinnacle's "Read Account Status" API
     */
    public function getAccountStatus(): ?array
    {
        try {
            $apiBase = 'https://smsportal.hostpinnacle.co.ke/SMSApi';
            $endpoint = $apiBase . '/readAccountStatus';
            
            $payload = http_build_query([
                'userid'   => $this->userId,
                'password' => $this->password,
                'output'   => 'json',
            ]);

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    "apikey: {$this->apiKey}",
                    "cache-control: no-cache",
                    "content-type: application/x-www-form-urlencoded",
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err      = curl_error($curl);
            curl_close($curl);

            if ($err || $httpCode !== 200) {
                Log::warning("Account status check failed", [
                    'http_code' => $httpCode,
                    'error' => $err
                ]);
                return null;
            }

            $decoded = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Invalid JSON response from account status API", [
                    'raw_response' => $response
                ]);
                return null;
            }

            Log::info("Account status retrieved", ['response' => $decoded]);
            return $decoded;
        } catch (\Exception $e) {
            Log::error("Exception getting account status: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send OTP SMS
     * @param string $phoneNumber
     * @param string $otpCode
     * @return array
     */
    public function sendOTP(string $phoneNumber, string $otpCode): array
    {
        $message = "Your verification code is: {$otpCode}. Valid for 10 minutes. Do not share this code with anyone.";
        return $this->sendSMS($phoneNumber, $message);
    }

    public function getFinanceSenderId(): string
    {
        return $this->financeSenderId ?: $this->senderId;
    }
}
