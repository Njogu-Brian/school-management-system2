<?php

namespace App\Services;

use App\Models\OtpVerification;
use App\Services\SMSService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class OtpService
{
    protected SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Normalize phone number for consistent storage/lookup
     */
    protected function normalizePhone(string $phone): string
    {
        return app(LoginIdentifierService::class)->normalizePhone($phone);
    }

    /**
     * Generate and send OTP
     *
     * @return array{success:bool, otp:?string, message:string, delivery_channel?:string}
     */
    public function generateAndSend(string $identifier, string $purpose = 'login', ?string $ipAddress = null, ?string $channel = null): array
    {
        try {
            $ids = app(LoginIdentifierService::class);
            $isPhone = $ids->isLikelyPhone($identifier, $channel);
            if ($isPhone) {
                $identifier = $this->normalizePhone($identifier);
            }

            OtpVerification::forIdentifier($identifier, $purpose)
                ->where('verified', false)
                ->update(['verified' => true]);

            $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            OtpVerification::create([
                'identifier' => $identifier,
                'otp_code' => $otpCode,
                'purpose' => $purpose,
                'expires_at' => Carbon::now()->addMinutes(10),
                'ip_address' => $ipAddress ?? request()->ip(),
            ]);

            if ($isPhone) {
                return $this->sendPhoneOtp($identifier, $otpCode, $purpose);
            }

            Mail::raw(
                "Your verification code is {$otpCode}. It expires in 10 minutes.",
                function ($message) use ($identifier, $purpose) {
                    $message->to($identifier)
                        ->subject(match ($purpose) {
                            'password_reset' => 'Password Reset OTP',
                            'login' => 'Login OTP',
                            'parent_claim' => 'Parent Account Verification OTP',
                            default => 'Verification OTP',
                        });
                }
            );

            Log::info('OTP sent via Email', [
                'identifier' => $identifier,
                'purpose' => $purpose,
            ]);

            return [
                'success' => true,
                'otp' => $otpCode,
                'message' => 'OTP sent successfully via email.',
                'delivery_channel' => 'email',
            ];
        } catch (\Exception $e) {
            Log::error('OTP generation failed', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'otp' => null,
                'message' => 'Failed to generate OTP. Please try again.',
            ];
        }
    }

    /**
     * Prefer SMS; when credits are unavailable, fall back to WhatsApp and tell the caller.
     *
     * @return array{success:bool, otp:?string, message:string, delivery_channel?:string}
     */
    protected function sendPhoneOtp(string $phone, string $otpCode, string $purpose): array
    {
        $smsBlocked = CommunicationPauseService::isPaused()
            || ! $this->smsService->hasSufficientCredits(
                SmsSegmentCalculator::segmentsForMessage(
                    "Your School ERP code is {$otpCode}\nExpires in 10 minutes. Do not share it."
                )
            );

        if (! $smsBlocked) {
            $result = $this->smsService->sendOTP($phone, $otpCode);
            $errorCode = is_array($result) ? ($result['error_code'] ?? null) : null;
            $status = is_array($result) ? strtolower((string) ($result['status'] ?? '')) : '';

            if ($errorCode === 'INSUFFICIENT_CREDITS') {
                CommunicationPauseService::pauseDueToInsufficientCredits(
                    (float) ($result['balance'] ?? 0),
                    'OtpService::sendPhoneOtp'
                );
                $smsBlocked = true;
            } elseif ($status === 'error') {
                Log::error('OTP SMS sending failed', [
                    'identifier' => $phone,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);
                // Try WhatsApp as a secondary recovery path for provider failures too when credits exist.
            } else {
                Log::info('OTP sent via SMS', [
                    'identifier' => $phone,
                    'purpose' => $purpose,
                ]);

                return [
                    'success' => true,
                    'otp' => $otpCode,
                    'message' => 'OTP sent successfully via SMS.',
                    'delivery_channel' => 'sms',
                ];
            }
        }

        $wa = app(WhatsAppService::class);
        $waResult = $wa->sendOtp($phone, $otpCode);
        $waOk = strtolower((string) ($waResult['status'] ?? '')) === 'success'
            || ! empty($waResult['message_id']);

        if ($waOk) {
            Log::info('OTP sent via WhatsApp (SMS unavailable)', [
                'identifier' => $phone,
                'purpose' => $purpose,
                'sms_blocked' => $smsBlocked,
            ]);

            return [
                'success' => true,
                'otp' => $otpCode,
                'message' => 'SMS service is currently unavailable. Your OTP will be sent via WhatsApp.',
                'delivery_channel' => 'whatsapp',
            ];
        }

        Log::error('OTP WhatsApp fallback failed', [
            'identifier' => $phone,
            'result' => $waResult,
        ]);

        return [
            'success' => false,
            'otp' => null,
            'message' => $smsBlocked
                ? 'SMS credits are unavailable and WhatsApp delivery failed. Please try email OTP or recharge SMS credits.'
                : 'Failed to send OTP. Please try again.',
            'delivery_channel' => null,
        ];
    }

    public function verify(string $identifier, string $otpCode, string $purpose = 'login'): array
    {
        $isPhone = app(LoginIdentifierService::class)->isLikelyPhone($identifier);
        if ($isPhone) {
            $identifier = $this->normalizePhone($identifier);
        }

        $otp = OtpVerification::forIdentifier($identifier, $purpose)
            ->valid()
            ->where('otp_code', $otpCode)
            ->first();

        if (! $otp && $isPhone) {
            $altIdentifier = ltrim($identifier, '+');
            $otp = OtpVerification::forIdentifier($altIdentifier, $purpose)
                ->valid()
                ->where('otp_code', $otpCode)
                ->first();
        }

        if (! $otp) {
            return [
                'valid' => false,
                'message' => 'Invalid or expired OTP code. Please request a new OTP.',
            ];
        }

        $otp->markAsVerified();

        return [
            'valid' => true,
            'message' => 'OTP verified successfully.',
        ];
    }

    public function cleanupExpired(): int
    {
        return OtpVerification::where('expires_at', '<', now())
            ->where('verified', false)
            ->delete();
    }
}
