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
        // Use config() instead of env() to support config caching in production
        // Fallback to env() if config is not cached (for development)
        $this->apiUrl   = config('services.sms.api_url', env('SMS_API_URL', 'https://smsportal.hostpinnacle.co.ke/SMSApi/send'));
        $this->apiKey   = config('services.sms.api_key', env('SMS_API_KEY'));
        $this->userId   = config('services.sms.user_id', env('SMS_USER_ID'));
        $this->password = config('services.sms.password', env('SMS_PASSWORD'));
        $this->senderId = config('services.sms.sender_id', env('SMS_SENDER_ID', 'ROYAL_KINGS'));
        $this->financeSenderId = config('services.sms.sender_id_finance', env('SMS_SENDER_ID_FINANCE', 'RKS_FINANCE'));
        // HostPinnacle API base URL
        $apiBase = 'https://smsportal.hostpinnacle.co.ke/SMSApi';
        // Use "Read Account Status" API for balance check (from HostPinnacle documentation)
        // Updated to use correct endpoint: GET /SMSApi/account/readstatus
        $this->balanceUrl = env('SMS_BALANCE_URL', $apiBase . '/account/readstatus');
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
            
            // If getAccountStatus didn't return balance, log the response for debugging
            if ($accountStatus) {
                Log::warning("Account status retrieved but balance field not found", [
                    'account_status_response' => $accountStatus,
                    'response_keys' => array_keys($accountStatus ?? []),
                    'suggestion' => 'Please verify the response format from HostPinnacle. The balance field may have a different name.'
                ]);
            }
            
            // Fallback: Try credit history endpoint (POST method)
            // According to documentation: POST /SMSApi/account/readcredithistory
            try {
                $apiBase = 'https://smsportal.hostpinnacle.co.ke/SMSApi';
                $creditHistoryEndpoint = $apiBase . '/account/readcredithistory';
                
                $payload = http_build_query([
                    'userid'   => $this->userId,
                    'password' => $this->password,
                    'output'   => 'json',
                ]);

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL            => $creditHistoryEndpoint,
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

                if (!$err && $httpCode === 200) {
                    $decoded = json_decode($response, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Try to extract balance from credit history
                        $balance = data_get($decoded, 'balance') 
                            ?? data_get($decoded, 'current_balance')
                            ?? data_get($decoded, 'credits')
                            ?? null;
                        
                        if ($balance !== null) {
                            $balance = (float) $balance;
                            Log::info("SMS Balance retrieved from credit history", [
                                'balance' => $balance,
                                'endpoint' => $creditHistoryEndpoint
                            ]);
                            return $balance;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::debug("Exception checking credit history: " . $e->getMessage());
            }

            // If all methods failed, log comprehensive error
            Log::error("Unable to check SMS balance", [
                'tried_methods' => [
                    'GET /SMSApi/account/readstatus',
                    'POST /SMSApi/account/readcredithistory'
                ],
                'userid' => $this->userId ? 'set' : 'missing',
                'api_key' => $this->apiKey ? 'set' : 'missing',
                'account_status_response' => $accountStatus,
                'suggestion' => 'Please verify the correct balance endpoint and response format with HostPinnacle support.'
            ]);
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
        // Validate required credentials BEFORE attempting to send
        // This prevents API calls with missing credentials
        if (empty($this->userId) || trim($this->userId) === '') {
            Log::error("SMS sending failed: SMS_USER_ID not configured", [
                'phone' => $phoneNumber,
                'user_id_set' => isset($this->userId),
                'user_id_value' => $this->userId ?? 'null'
            ]);
            return [
                'status' => 'error',
                'statusCode' => '213',
                'reason' => 'Parameter userid required.',
                'message' => 'SMS_USER_ID environment variable is not set or is empty',
                'error_code' => 'MISSING_USER_ID'
            ];
        }

        if (empty($this->password) || trim($this->password) === '') {
            Log::error("SMS sending failed: SMS_PASSWORD not configured", [
                'phone' => $phoneNumber,
            ]);
            return [
                'status' => 'error',
                'statusCode' => '214',
                'reason' => 'Parameter password required.',
                'message' => 'SMS_PASSWORD environment variable is not set or is empty',
                'error_code' => 'MISSING_PASSWORD'
            ];
        }

        if (empty($this->apiKey) || trim($this->apiKey) === '') {
            Log::error("SMS sending failed: SMS_API_KEY not configured", [
                'phone' => $phoneNumber,
            ]);
            return [
                'status' => 'error',
                'statusCode' => '215',
                'reason' => 'API key required.',
                'message' => 'SMS_API_KEY environment variable is not set or is empty',
                'error_code' => 'MISSING_API_KEY'
            ];
        }

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

        // CRITICAL: If msgId is empty despite success, the message was NOT queued
        // This typically happens when balance is 0 or account has issues
        if (empty($decoded['msgId']) && isset($decoded['status']) && $decoded['status'] === 'success') {
            // Immediately check balance to diagnose the issue
            $balance = $this->checkBalance(true); // Force fresh check
            
            Log::error("SMS provider returned success but msgId is empty - message may not be queued for delivery", [
                'phone' => $phoneNumber,
                'transaction_id' => $decoded['transactionId'] ?? null,
                'response' => $decoded,
                'balance_check' => $balance,
                'likely_cause' => $balance === null 
                    ? 'Unable to check balance - account may be suspended or endpoint incorrect'
                    : ($balance <= 0 
                        ? 'Insufficient balance (balance: ' . $balance . ')' 
                        : 'Account issue - contact SMS provider'),
                'action_required' => 'Check SMS account balance and status with provider'
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
     * Get account status (includes balance) using HostPinnacle's API
     * According to HostPinnacle documentation: GET /SMSApi/account/readstatus
     * NOTE: API key cannot be used in GET method - only userid and password
     */
    public function getAccountStatus(): ?array
    {
        $apiBase = 'https://smsportal.hostpinnacle.co.ke/SMSApi';
        
        // Primary endpoint from HostPinnacle documentation
        $endpoint = $apiBase . '/account/readstatus';
        
        // Build query string for GET request
        // NOTE: According to documentation, API key cannot be used in GET method
        $queryParams = http_build_query([
            'userid'   => $this->userId,
            'password' => $this->password,
            'output'   => 'json',
        ]);
        
        $fullUrl = $endpoint . '?' . $queryParams;

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $fullUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPGET        => true, // Use GET method
                CURLOPT_HTTPHEADER     => [
                    "cache-control: no-cache",
                    "accept: application/json",
                ],
                // NOTE: NOT including API key header - documentation states API key cannot be used in GET method
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err      = curl_error($curl);
            curl_close($curl);

            if ($err) {
                Log::error("Account status check failed - cURL error", [
                    'endpoint' => $endpoint,
                    'http_code' => $httpCode,
                    'error' => $err
                ]);
                return null;
            }

            if ($httpCode !== 200) {
                Log::warning("Account status check failed - HTTP error", [
                    'endpoint' => $endpoint,
                    'http_code' => $httpCode,
                    'response_body' => substr($response, 0, 1000),
                    'note' => $httpCode === 404 
                        ? 'Endpoint not found. Please verify the endpoint URL with HostPinnacle support.'
                        : 'Unexpected HTTP status code'
                ]);
                return null;
            }

            $decoded = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Invalid JSON response from account status API", [
                    'endpoint' => $endpoint,
                    'raw_response' => substr($response, 0, 1000),
                    'json_error' => json_last_error_msg()
                ]);
                return null;
            }

            Log::info("Account status retrieved successfully", [
                'endpoint' => $endpoint,
                'method' => 'GET',
                'response' => $decoded,
                'code_version' => 'v2.0 - Using correct HostPinnacle endpoint'
            ]);
            return $decoded;
        } catch (\Exception $e) {
            Log::error("Exception getting account status: " . $e->getMessage(), [
                'endpoint' => $endpoint,
                'trace' => $e->getTraceAsString()
            ]);
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
