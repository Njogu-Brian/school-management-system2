<?php

namespace App\Services\PaymentGateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaGateway implements PaymentGatewayInterface
{
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $shortcode;
    protected string $passkey;
    protected string $environment;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->consumerKey = config('mpesa.consumer_key') ?? config('services.mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret') ?? config('services.mpesa.consumer_secret');
        $this->shortcode = config('mpesa.shortcode') ?? config('services.mpesa.shortcode');
        $this->passkey = config('mpesa.passkey') ?? config('services.mpesa.passkey');
        $this->environment = config('mpesa.environment') ?? config('services.mpesa.environment', 'sandbox');
        
        // Verify environment configuration (simplified logging)
        $urls = $this->environment === 'production' 
            ? config('mpesa.production_urls')
            : config('mpesa.sandbox_urls');
        $oauthUrl = $urls['oauth'] ?? 'unknown';
        
        // Check for configuration errors
        if ($this->environment === 'production' && strpos($oauthUrl, 'sandbox.safaricom.co.ke') !== false) {
            Log::error('M-PESA CONFIGURATION ERROR: Environment is production but using sandbox URLs! Set MPESA_ENVIRONMENT=production in .env and clear cache.');
        }
        
        // Validate credentials are configured
        if (empty($this->consumerKey) || empty($this->consumerSecret)) {
            Log::warning('M-PESA credentials not fully configured', [
                'has_consumer_key' => !empty($this->consumerKey),
                'has_consumer_secret' => !empty($this->consumerSecret),
            ]);
        }
    }

    /**
     * Get API URL based on environment
     */
    protected function getUrl(string $endpoint): string
    {
        $urls = $this->environment === 'production' 
            ? config('mpesa.production_urls')
            : config('mpesa.sandbox_urls');
        
        return $urls[$endpoint] ?? throw new \Exception("Unknown M-PESA endpoint: {$endpoint}");
    }

    /**
     * Get access token (always fresh - no caching)
     * Fetches a new token from M-PESA for every request
     */
    protected function getAccessToken(): string
    {
        // Validate credentials before attempting
        if (empty($this->consumerKey) || empty($this->consumerSecret)) {
            Log::error('M-PESA credentials missing', [
                'has_consumer_key' => !empty($this->consumerKey),
                'has_consumer_secret' => !empty($this->consumerSecret),
                'environment' => $this->environment,
            ]);
            throw new \Exception('M-PESA Consumer Key or Consumer Secret is not configured. Check your .env file.');
        }

        try {
            $url = $this->getUrl('oauth') . '?grant_type=client_credentials';

            // Get fresh token (logging simplified)

            $response = Http::timeout(30)
                ->withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get($url);

            if ($response->successful() && $response->json('access_token')) {
                $accessToken = $response->json('access_token');
                
                // Log token obtained (simplified)
                Log::debug('M-PESA access token obtained', [
                    'environment' => $this->environment,
                    'token_length' => strlen($accessToken),
                ]);
                
                return $accessToken;
            }

            // Log the full error response
            $errorData = $response->json();
            Log::error('Failed to get M-PESA access token', [
                'status' => $response->status(),
                'response' => $errorData,
                'url' => $url,
                'environment' => $this->environment,
            ]);

            $errorMessage = $errorData['error_description'] ?? $errorData['errorMessage'] ?? 'Unknown error';
            throw new \Exception('Failed to get M-PESA access token: ' . $errorMessage . ' (Status: ' . $response->status() . ')');

        } catch (\Exception $e) {
            Log::error('M-PESA authentication exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Clear cached access token (no-op since we don't cache anymore)
     * Kept for backward compatibility with existing code
     */
    public function clearAccessToken(): void
    {
        // No longer caching tokens, so nothing to clear
        // But we clear the instance variable if it exists
        $this->accessToken = null;
        
        // Also clear any existing cache entry (in case it was cached before)
        $cacheKey = 'mpesa_access_token_' . md5($this->consumerKey);
        cache()->forget($cacheKey);
        
        Log::debug('M-PESA access token cache cleared (tokens are now always fresh)', [
            'environment' => $this->environment,
        ]);
    }

    /**
     * Validate M-PESA credentials configuration
     */
    public function validateCredentials(): array
    {
        $issues = [];
        $warnings = [];
        
        // Check environment
        if (empty($this->environment)) {
            $issues[] = 'MPESA_ENVIRONMENT is not set';
        } elseif ($this->environment !== 'production' && $this->environment !== 'sandbox') {
            $issues[] = "MPESA_ENVIRONMENT must be 'production' or 'sandbox', got: {$this->environment}";
        }
        
        // Check credentials
        if (empty($this->consumerKey)) {
            $issues[] = 'MPESA_CONSUMER_KEY is missing';
        }
        if (empty($this->consumerSecret)) {
            $issues[] = 'MPESA_CONSUMER_SECRET is missing';
        }
        if (empty($this->shortcode)) {
            $issues[] = 'MPESA_SHORTCODE is missing';
        }
        if (empty($this->passkey)) {
            $issues[] = 'MPESA_PASSKEY is missing';
        }
        
        // Check environment-specific requirements
        if ($this->environment === 'production') {
            $urls = config('mpesa.production_urls');
            $oauthUrl = $urls['oauth'] ?? null;
            
            if (strpos($oauthUrl, 'sandbox.safaricom.co.ke') !== false) {
                $issues[] = 'Production environment but using sandbox URLs - Check MPESA_ENVIRONMENT setting';
            }
            
            if ($this->shortcode == '174379') {
                $warnings[] = 'Shortcode 174379 is the sandbox test number - Are you using production credentials?';
            }
        }
        
        // Try to get access token
        $tokenObtained = false;
        $tokenError = null;
        try {
            $token = $this->getAccessToken();
            $tokenObtained = !empty($token);
        } catch (\Exception $e) {
            $tokenError = $e->getMessage();
            $issues[] = "Failed to get access token: {$tokenError}";
        }
        
        return [
            'valid' => empty($issues) && $tokenObtained,
            'environment' => $this->environment ?? 'not set',
            'has_consumer_key' => !empty($this->consumerKey),
            'has_consumer_secret' => !empty($this->consumerSecret),
            'shortcode' => $this->shortcode ?? 'NOT SET',
            'has_passkey' => !empty($this->passkey),
            'oauth_url' => $this->getUrl('oauth'),
            'stk_push_url' => $this->getUrl('stk_push'),
            'token_obtained' => $tokenObtained,
            'token_error' => $tokenError,
            'issues' => $issues,
            'warnings' => $warnings,
            'recommendations' => $this->getRecommendations($issues, $warnings),
        ];
    }
    
    /**
     * Get recommendations based on validation issues
     */
    protected function getRecommendations(array $issues, array $warnings): array
    {
        $recommendations = [];
        
        if (!empty($issues)) {
            if (in_array('Failed to get access token', $issues)) {
                $recommendations[] = 'Verify your IP address is whitelisted in Safaricom Daraja portal';
                $recommendations[] = 'Ensure your Consumer Key and Secret match the shortcode and passkey';
                $recommendations[] = 'Check that your app is approved for production environment';
            }
            
            if (strpos(implode(', ', $issues), 'environment') !== false) {
                $recommendations[] = "Set MPESA_ENVIRONMENT=production in .env file";
                $recommendations[] = 'Run: php artisan config:clear && php artisan cache:clear';
            }
        }
        
        if (!empty($warnings)) {
            $recommendations[] = 'Review your credentials - ensure they are from the production Daraja app';
        }
        
        return $recommendations;
    }

    /**
     * Test M-PESA credentials and connection
     */
    public function testCredentials(): array
    {
        $results = [
            'configured' => false,
            'credentials_valid' => false,
            'token_obtained' => false,
            'environment' => $this->environment,
            'errors' => [],
            'details' => [],
        ];

        // Check if credentials are configured
        if (empty($this->consumerKey) || empty($this->consumerSecret) || empty($this->shortcode) || empty($this->passkey)) {
            $results['errors'][] = 'Missing required credentials';
            $results['details']['has_consumer_key'] = !empty($this->consumerKey);
            $results['details']['has_consumer_secret'] = !empty($this->consumerSecret);
            $results['details']['has_shortcode'] = !empty($this->shortcode);
            $results['details']['has_passkey'] = !empty($this->passkey);
            return $results;
        }

        $results['configured'] = true;
        $results['details']['consumer_key_length'] = strlen($this->consumerKey);
        $results['details']['consumer_secret_length'] = strlen($this->consumerSecret);
        $results['details']['shortcode'] = $this->shortcode;

        // Test getting access token
        try {
            $this->clearAccessToken(); // Force fresh token
            $token = $this->getAccessToken();
            
            if ($token) {
                $results['credentials_valid'] = true;
                $results['token_obtained'] = true;
                $results['details']['token_length'] = strlen($token);
                $results['details']['token_preview'] = substr($token, 0, 20) . '...';
            }
        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            $results['details']['exception'] = get_class($e);
        }

        return $results;
    }

    /**
     * Initiate STK Push - Simplified version
     */
    public function initiatePayment(PaymentTransaction $transaction, array $options = []): array
    {
        // Validate required fields
        $phoneNumber = $options['phone_number'] ?? null;
        if (!$phoneNumber) {
            throw new \Exception('Phone number is required for M-Pesa payment');
        }

        // Format phone number to 254XXXXXXXXX
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        // Validate credentials are configured
        if (empty($this->shortcode) || empty($this->passkey)) {
            throw new \Exception('M-PESA shortcode and passkey are required. Please configure in settings.');
        }

        // Get fresh access token
        try {
            $accessToken = $this->getAccessToken();
        } catch (\Exception $e) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => 'Failed to authenticate with M-PESA: ' . $e->getMessage(),
            ]);
            throw new \Exception('M-PESA authentication failed. Please check your credentials.');
        }

        // Prepare STK Push payload
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round($transaction->amount),
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => route('payment.webhook.mpesa'),
            'AccountReference' => $transaction->reference,
            'TransactionDesc' => 'School Fee Payment - ' . $transaction->reference,
        ];

        $url = $this->getUrl('stk_push');

        // Make STK Push request
        try {
            $response = Http::timeout(60)
                ->withToken($accessToken)
                ->post($url, $payload);

            $responseData = $response->json();

            // Handle successful response
            if ($response->successful() && isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
                $transaction->update([
                    'transaction_id' => $responseData['CheckoutRequestID'],
                    'gateway_response' => $responseData,
                    'status' => 'processing',
                ]);

                return [
                    'success' => true,
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'message' => 'STK Push sent successfully. Please check your phone.',
                ];
            }

            // Handle error response
            $errorCode = $responseData['errorCode'] ?? null;
            $errorMessage = $responseData['errorMessage'] ?? $responseData['ResponseDescription'] ?? 'STK Push failed';

            // Special handling for common errors
            if ($errorCode == '404.001.03') {
                $errorMessage = 'M-PESA authentication failed. Please verify: 1) Your IP is whitelisted in Daraja portal, 2) Credentials match the app, 3) App is approved for production.';
            }

            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $errorMessage,
            ]);

            // Log error (simplified)
            Log::error('M-PESA STK Push failed', [
                'transaction_id' => $transaction->id,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'phone' => $phoneNumber,
                'amount' => $transaction->amount,
            ]);

            return [
                'success' => false,
                'message' => $errorMessage,
            ];

        } catch (\Exception $e) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => 'Network error: ' . $e->getMessage(),
            ]);

            Log::error('M-PESA STK Push network error', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to send STK Push: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $transactionId): array
    {
        $url = $this->getUrl('stk_push_query');

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $transactionId,
        ];

        $response = Http::withToken($this->getAccessToken())
            ->post($url, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('M-PESA STK Push Query failed', [
            'transaction_id' => $transactionId,
            'response' => $response->json(),
        ]);

        return [
            'success' => false,
            'message' => 'Failed to query payment status',
            'response' => $response->json(),
        ];
    }

    /**
     * Process webhook callback
     */
    public function processWebhook(array $payload, string $signature): array
    {
        // M-Pesa doesn't use signature verification in the same way
        // But we validate the callback structure
        if (!isset($payload['Body']['stkCallback'])) {
            throw new \Exception('Invalid M-Pesa webhook payload');
        }

        $callback = $payload['Body']['stkCallback'];
        $resultCode = $callback['ResultCode'] ?? null;
        $checkoutRequestId = $callback['CheckoutRequestID'] ?? null;

        if ($resultCode == 0) {
            // Payment successful
            $callbackMetadata = $callback['CallbackMetadata']['Item'] ?? [];
            $items = [];
            foreach ($callbackMetadata as $item) {
                $items[$item['Name']] = $item['Value'] ?? null;
            }

            return [
                'success' => true,
                'checkout_request_id' => $checkoutRequestId,
                'mpesa_receipt_number' => $items['MpesaReceiptNumber'] ?? null,
                'transaction_date' => $items['TransactionDate'] ?? null,
                'phone_number' => $items['PhoneNumber'] ?? null,
                'amount' => $items['Amount'] ?? null,
            ];
        }

        return [
            'success' => false,
            'checkout_request_id' => $checkoutRequestId,
            'result_code' => $resultCode,
            'result_desc' => $callback['ResultDesc'] ?? 'Payment failed',
        ];
    }

    /**
     * Verify webhook signature (M-Pesa uses IP whitelist instead)
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool
    {
        // M-Pesa uses IP whitelist for security
        // In production, verify request comes from Safaricom IPs
        return true; // Simplified for now
    }

    /**
     * Refund payment
     */
    public function refund(PaymentTransaction $transaction, ?float $amount = null): array
    {
        // M-Pesa refunds are typically done manually or via reversal API
        // This would require additional M-Pesa API setup
        throw new \Exception('M-Pesa refunds not yet implemented');
    }

    /**
     * Initiate STK Push for admin-prompted payment
     * 
     * @param int $studentId
     * @param string $phoneNumber
     * @param float $amount
     * @param string|null $invoiceId
     * @param int|null $adminId
     * @param string|null $notes
     * @return array
     */
    public function initiateAdminPromptedPayment(
        int $studentId,
        string $phoneNumber,
        float $amount,
        ?int $invoiceId = null,
        ?int $adminId = null,
        ?string $notes = null
    ): array {
        $student = \App\Models\Student::findOrFail($studentId);
        
        // Create payment transaction record
        $transaction = \App\Models\PaymentTransaction::create([
            'student_id' => $studentId,
            'invoice_id' => $invoiceId,
            'gateway' => 'mpesa',
            'reference' => 'ADM-' . $student->admission_number . '-' . time(),
            'amount' => $amount,
            'currency' => 'KES',
            'status' => 'pending',
            'initiated_by' => $adminId,
            'admin_notes' => $notes,
            'phone_number' => $phoneNumber,
            'account_reference' => $student->admission_number,
        ]);

        try {
            $result = $this->initiatePayment($transaction, [
                'phone_number' => $phoneNumber,
            ]);

            return array_merge($result, [
                'transaction_id' => $transaction->id,
                'student' => $student,
            ]);
        } catch (\Exception $e) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
                'transaction_id' => $transaction->id,
            ];
        }
    }

    /**
     * Initiate payment from payment link
     * 
     * @param \App\Models\PaymentLink $paymentLink
     * @param string $phoneNumber
     * @param float|null $amount
     * @return array
     */
    public function initiatePaymentFromLink(
        \App\Models\PaymentLink $paymentLink,
        string $phoneNumber,
        ?float $amount = null
    ): array {
        if (!$paymentLink->isActive()) {
            return [
                'success' => false,
                'message' => 'Payment link is no longer active',
            ];
        }

        // Use provided amount or default to link amount
        $paymentAmount = $amount ?? $paymentLink->amount;
        
        // Validate amount doesn't exceed link amount
        if ($paymentAmount > $paymentLink->amount) {
            return [
                'success' => false,
                'message' => 'Payment amount exceeds the maximum allowed',
            ];
        }

        // Create payment transaction
        $transaction = \App\Models\PaymentTransaction::create([
            'student_id' => $paymentLink->student_id,
            'invoice_id' => $paymentLink->invoice_id,
            'payment_link_id' => $paymentLink->id,
            'gateway' => 'mpesa',
            'reference' => $paymentLink->payment_reference,
            'amount' => $paymentAmount,
            'currency' => $paymentLink->currency,
            'status' => 'pending',
            'phone_number' => $phoneNumber,
            'account_reference' => $paymentLink->student->admission_number,
        ]);

        try {
            $result = $this->initiatePayment($transaction, [
                'phone_number' => $phoneNumber,
            ]);

            if ($result['success']) {
                $paymentLink->incrementUseCount();
            }

            return array_merge($result, [
                'transaction_id' => $transaction->id,
                'payment_link_id' => $paymentLink->id,
            ]);
        } catch (\Exception $e) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
                'transaction_id' => $transaction->id,
            ];
        }
    }

    /**
     * Query STK Push status
     * 
     * @param string $checkoutRequestId
     * @return array
     */
    public function queryStkPushStatus(string $checkoutRequestId): array
    {
        try {
            $response = $this->verifyPayment($checkoutRequestId);
            
            return [
                'success' => true,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to M-Pesa format (254XXXXXXXXX)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Convert to 254 format
        if (strlen($phone) == 9 && substr($phone, 0, 1) == '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (strlen($phone) == 9) {
            $phone = '254' . $phone;
        }

        return $phone;
    }

    /**
     * Get formatted phone number for display
     */
    public static function formatPhoneNumberForDisplay(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            return '0' . substr($phone, 3);
        }
        
        return $phone;
    }

    /**
     * Validate phone number
     * Accepts: 254XXXXXXXXX (12 digits), 07XXXXXXXX (10 digits), 7XXXXXXXX (9 digits)
     * Kenyan mobile prefixes: 7X (Safaricom/Airtel), 1X (Airtel/Telkom)
     * Format breakdown:
     * - 254XXXXXXXXX = 254 (3) + 7XXXXXXXX or 1XXXXXXXX (9) = 12 total
     * - 07XXXXXXXX = 0 (1) + 7XXXXXXX or 1XXXXXXX (8) = 10 total  
     * - 7XXXXXXXX = 7XXXXXXX or 1XXXXXXX (9) = 9 total
     */
    public static function isValidKenyanPhone(string $phone): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format: 254XXXXXXXXX (12 digits) - International format
        // After 254 (3 digits), we need 9 more digits starting with 7 or 1
        if (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            // Check if the 4th character (after 254) is 7 or 1
            $fourthChar = substr($phone, 3, 1);
            if (in_array($fourthChar, ['7', '1'])) {
                // Verify it's exactly 12 digits: 254 + 9 digits starting with 7 or 1
                return preg_match('/^254[17]\d{8}$/', $phone);
            }
            return false;
        }
        
        // Format: 07XXXXXXXX or 01XXXXXXXX (10 digits) - Local format with leading 0
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            // Check if it's 07XXXXXXXX or 01XXXXXXXX
            return preg_match('/^0[17]\d{8}$/', $phone);
        }
        
        // Format: 7XXXXXXXX or 1XXXXXXXX (9 digits) - Without leading 0
        if (strlen($phone) == 9) {
            // Check if it starts with 7 or 1
            return preg_match('/^[17]\d{8}$/', $phone);
        }
        
        return false;
    }

    /**
     * Query transaction status
     * 
     * @param string $transactionId M-PESA transaction ID (e.g., receipt number)
     * @return array
     */
    public function queryTransactionStatus(string $transactionId): array
    {
        $url = $this->getUrl('transaction_status');

        $timestamp = now()->format('YmdHis');
        $securityCredential = $this->generateSecurityCredential();

        $payload = [
            'Initiator' => config('mpesa.initiator_name'),
            'SecurityCredential' => $securityCredential,
            'CommandID' => 'TransactionStatusQuery',
            'TransactionID' => $transactionId,
            'PartyA' => $this->shortcode,
            'IdentifierType' => '4', // 4 = Organization shortcode
            'ResultURL' => config('mpesa.result_url'),
            'QueueTimeOutURL' => config('mpesa.queue_timeout_url'),
            'Remarks' => 'Transaction status query',
            'Occasion' => 'Status Check',
        ];

        try {
            $response = Http::withToken($this->getAccessToken())
                ->timeout(config('mpesa.timeouts.transaction_query', 30))
                ->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('M-PESA Transaction Status Query initiated', [
                    'transaction_id' => $transactionId,
                    'response' => $responseData,
                ]);

                return [
                    'success' => true,
                    'conversation_id' => $responseData['ConversationID'] ?? null,
                    'originator_conversation_id' => $responseData['OriginatorConversationID'] ?? null,
                    'response_description' => $responseData['ResponseDescription'] ?? 'Query accepted for processing',
                ];
            }

            Log::error('M-PESA Transaction Status Query failed', [
                'transaction_id' => $transactionId,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to query transaction status',
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('M-PESA Transaction Status Query exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Register C2B URLs
     * 
     * This registers the validation and confirmation URLs for C2B payments
     * 
     * @return array
     */
    public function registerC2BUrls(): array
    {
        $url = $this->getUrl('c2b_register');

        $payload = [
            'ShortCode' => $this->shortcode,
            'ResponseType' => config('mpesa.c2b.response_type', 'Completed'),
            'ConfirmationURL' => config('mpesa.confirmation_url'),
            'ValidationURL' => config('mpesa.validation_url'),
        ];

        try {
            $response = Http::withToken($this->getAccessToken())
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('M-PESA C2B URLs registered successfully', [
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'message' => 'C2B URLs registered successfully',
                    'response' => $response->json(),
                ];
            }

            Log::error('M-PESA C2B URL registration failed', [
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to register C2B URLs',
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('M-PESA C2B URL registration exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Query account balance
     * 
     * @return array
     */
    public function queryAccountBalance(): array
    {
        $url = $this->getUrl('account_balance');

        $securityCredential = $this->generateSecurityCredential();

        $payload = [
            'Initiator' => config('mpesa.initiator_name'),
            'SecurityCredential' => $securityCredential,
            'CommandID' => 'AccountBalance',
            'PartyA' => $this->shortcode,
            'IdentifierType' => '4', // 4 = Organization shortcode
            'Remarks' => 'Account balance query',
            'QueueTimeOutURL' => config('mpesa.queue_timeout_url'),
            'ResultURL' => config('mpesa.result_url'),
        ];

        try {
            $response = Http::withToken($this->getAccessToken())
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('M-PESA Account Balance Query initiated', [
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Balance query initiated. Results will be sent to callback URL.',
                    'response' => $response->json(),
                ];
            }

            Log::error('M-PESA Account Balance Query failed', [
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to query account balance',
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('M-PESA Account Balance Query exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate security credential for API requests
     * 
     * @return string
     */
    protected function generateSecurityCredential(): string
    {
        $initiatorPassword = config('mpesa.initiator_password');
        
        // In production, this should be encrypted with M-PESA public key
        // For now, returning the password as-is (works in sandbox)
        // TODO: Implement proper RSA encryption with M-PESA public certificate
        
        return base64_encode($initiatorPassword);
    }

    /**
     * Reverse a transaction (Refund)
     * 
     * @param string $transactionId M-PESA transaction ID to reverse
     * @param float $amount Amount to reverse
     * @param string $remarks Reason for reversal
     * @return array
     */
    public function reverseTransaction(string $transactionId, float $amount, string $remarks = 'Transaction reversal'): array
    {
        $url = $this->getUrl('reversal');

        $securityCredential = $this->generateSecurityCredential();

        $payload = [
            'Initiator' => config('mpesa.initiator_name'),
            'SecurityCredential' => $securityCredential,
            'CommandID' => 'TransactionReversal',
            'TransactionID' => $transactionId,
            'Amount' => (int) $amount,
            'ReceiverParty' => $this->shortcode,
            'RecieverIdentifierType' => '11', // 11 = Till Number
            'ResultURL' => config('mpesa.result_url'),
            'QueueTimeOutURL' => config('mpesa.queue_timeout_url'),
            'Remarks' => $remarks,
            'Occasion' => 'Refund',
        ];

        try {
            $response = Http::withToken($this->getAccessToken())
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('M-PESA Transaction Reversal initiated', [
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Reversal initiated. Results will be sent to callback URL.',
                    'response' => $response->json(),
                ];
            }

            Log::error('M-PESA Transaction Reversal failed', [
                'transaction_id' => $transactionId,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate reversal',
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error('M-PESA Transaction Reversal exception', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception occurred: ' . $e->getMessage(),
            ];
        }
    }
}

