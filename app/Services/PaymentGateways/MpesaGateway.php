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
     * Get access token
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $url = $this->getUrl('oauth') . '?grant_type=client_credentials';

        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get($url);

        if ($response->successful()) {
            $this->accessToken = $response->json('access_token');
            return $this->accessToken;
        }

        throw new \Exception('Failed to get M-Pesa access token: ' . ($response->json('error_description') ?? 'Unknown error'));
    }

    /**
     * Initiate STK Push
     */
    public function initiatePayment(PaymentTransaction $transaction, array $options = []): array
    {
        $phoneNumber = $options['phone_number'] ?? null;
        if (!$phoneNumber) {
            throw new \Exception('Phone number is required for M-Pesa payment');
        }

        // Format phone number (254XXXXXXXXX)
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

        $url = $this->getUrl('stk_push');

        $payload = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) $transaction->amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => route('payment.webhook.mpesa'),
            'AccountReference' => $transaction->reference,
            'TransactionDesc' => 'School Fee Payment - ' . $transaction->reference,
        ];

        $response = Http::withToken($this->getAccessToken())
            ->post($url, $payload);

        $responseData = $response->json();

        if ($response->successful() && isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
            // Update transaction with CheckoutRequestID
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

        Log::error('M-Pesa STK Push failed', [
            'transaction_id' => $transaction->id,
            'response' => $responseData,
        ]);

        $transaction->update([
            'status' => 'failed',
            'failure_reason' => $responseData['errorMessage'] ?? 'STK Push failed',
        ]);

        return [
            'success' => false,
            'message' => $responseData['errorMessage'] ?? 'Payment initiation failed',
        ];
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
     */
    public static function isValidKenyanPhone(string $phone): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid Kenyan number (254XXXXXXXXX or 07XXXXXXXX or 01XXXXXXXX)
        if (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            return in_array(substr($phone, 3, 2), ['07', '01', '11']);
        }
        
        if (strlen($phone) == 10 && in_array(substr($phone, 0, 2), ['07', '01', '11'])) {
            return true;
        }
        
        if (strlen($phone) == 9 && in_array(substr($phone, 0, 1), ['7', '1'])) {
            return true;
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

