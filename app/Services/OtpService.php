<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Services\SMSService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OtpService
{
    protected SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Generate and send OTP
     * @param string $identifier Email or phone number
     * @param string $purpose Purpose of OTP (login, password_reset, etc.)
     * @param string|null $ipAddress IP address of requester
     * @return array ['success' => bool, 'otp' => string|null, 'message' => string]
     */
    /**
     * Normalize phone number for consistent storage/lookup
     */
    protected function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Ensure it starts with + (for international format)
        if (!str_starts_with($phone, '+')) {
            // If it starts with 0, replace with country code
            if (str_starts_with($phone, '0')) {
                $phone = '+254' . substr($phone, 1);
            } else {
                // Assume it's already in international format without +
                $phone = '+' . $phone;
            }
        }
        
        return $phone;
    }

    public function generateAndSend(string $identifier, string $purpose = 'login', ?string $ipAddress = null): array
    {
        try {
            // Normalize phone number if it's a phone
            $isPhone = preg_match('/^\+?[0-9]{10,15}$/', str_replace([' ', '-', '(', ')'], '', $identifier));
            if ($isPhone) {
                $identifier = $this->normalizePhone($identifier);
            }
            
            // Invalidate any existing unverified OTPs for this identifier
            OtpVerification::forIdentifier($identifier, $purpose)
                ->where('verified', false)
                ->update(['verified' => true]); // Mark as used

            // Generate 6-digit OTP
            $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // Create OTP record (expires in 10 minutes)
            $otp = OtpVerification::create([
                'identifier' => $identifier,
                'otp_code' => $otpCode,
                'purpose' => $purpose,
                'expires_at' => Carbon::now()->addMinutes(10),
                'ip_address' => $ipAddress ?? request()->ip(),
            ]);

            if ($isPhone) {
                // Send OTP via SMS
                $result = $this->smsService->sendOTP($identifier, $otpCode);
                
                if (isset($result['status']) && $result['status'] === 'error') {
                    Log::error('OTP SMS sending failed', [
                        'identifier' => $identifier,
                        'error' => $result['message'] ?? 'Unknown error'
                    ]);
                    return [
                        'success' => false,
                        'otp' => null,
                        'message' => 'Failed to send OTP. Please try again.'
                    ];
                }

                Log::info('OTP sent via SMS', [
                    'identifier' => $identifier,
                    'purpose' => $purpose
                ]);
            } else {
                // Send OTP via Email (implement email sending here)
                // For now, log it
                Log::info('OTP generated for email (email sending not implemented)', [
                    'identifier' => $identifier,
                    'otp' => $otpCode,
                    'purpose' => $purpose
                ]);
                
                // TODO: Implement email OTP sending
                return [
                    'success' => false,
                    'otp' => null,
                    'message' => 'Email OTP not yet implemented. Please use phone number.'
                ];
            }

            return [
                'success' => true,
                'otp' => $otpCode, // Only return in development, remove in production
                'message' => 'OTP sent successfully'
            ];
        } catch (\Exception $e) {
            Log::error('OTP generation failed', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'otp' => null,
                'message' => 'Failed to generate OTP. Please try again.'
            ];
        }
    }

    /**
     * Verify OTP
     * @param string $identifier
     * @param string $otpCode
     * @param string $purpose
     * @return array ['valid' => bool, 'message' => string]
     */
    public function verify(string $identifier, string $otpCode, string $purpose = 'login'): array
    {
        // Normalize phone number if it's a phone
        $isPhone = preg_match('/^\+?[0-9]{10,15}$/', str_replace([' ', '-', '(', ')'], '', $identifier));
        if ($isPhone) {
            $identifier = $this->normalizePhone($identifier);
        }
        
        // Try exact match first
        $otp = OtpVerification::forIdentifier($identifier, $purpose)
            ->valid()
            ->where('otp_code', $otpCode)
            ->first();

        // If not found and it's a phone, try alternative formats
        if (!$otp && $isPhone) {
            $altIdentifier = ltrim($identifier, '+');
            $otp = OtpVerification::forIdentifier($altIdentifier, $purpose)
                ->valid()
                ->where('otp_code', $otpCode)
                ->first();
        }

        if (!$otp) {
            // Debug: Check what OTPs exist for this purpose
            $existingOtps = OtpVerification::where('purpose', $purpose)
                ->where('otp_code', $otpCode)
                ->get();
            
            Log::warning('OTP verification failed', [
                'identifier' => $identifier,
                'purpose' => $purpose,
                'code_length' => strlen($otpCode),
                'existing_otps_count' => $existingOtps->count(),
                'existing_identifiers' => $existingOtps->pluck('identifier')->toArray(),
                'tried_identifier' => $identifier,
                'tried_alt' => $isPhone ? ltrim($identifier, '+') : null
            ]);
            
            return [
                'valid' => false,
                'message' => 'Invalid or expired OTP code. Please request a new OTP.'
            ];
        }

        // Mark as verified
        $otp->markAsVerified();

        Log::info('OTP verified successfully', [
            'identifier' => $identifier,
            'purpose' => $purpose
        ]);

        return [
            'valid' => true,
            'message' => 'OTP verified successfully.'
        ];
    }

    /**
     * Clean up expired OTPs (can be run via scheduled task)
     */
    public function cleanupExpired(): int
    {
        return OtpVerification::where('expires_at', '<', now())
            ->where('verified', false)
            ->delete();
    }
}

