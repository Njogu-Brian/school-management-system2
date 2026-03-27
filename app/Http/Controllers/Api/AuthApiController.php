<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\SMSService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

class AuthApiController extends Controller
{
    /**
     * Login - returns token and user for mobile app.
     */
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required_without:email|string',
            'email' => 'nullable|string',
            'password' => 'required',
        ]);

        $identifier = (string) $request->input('identifier', $request->input('email', ''));
        [$user] = $this->resolveUserAndStaff($identifier);

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $user->load('roles', 'roles.permissions', 'staff');

        // Revoke other tokens for this user (single device) or keep many - we'll keep one per login
        $user->tokens()->delete();

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $this->formatUserForApi($user),
            ],
        ]);
    }

    public function requestLoginOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'identifier' => 'required_without:email|string',
            'email' => 'nullable|string',
        ]);

        $identifier = (string) $request->input('identifier', $request->input('email', ''));
        [$user, $staff] = $this->resolveUserAndStaff($identifier);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with these details.',
            ], 404);
        }

        $phone = $this->resolvePhoneForUser($user, $staff);
        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'No phone number found for this account. Use password login.',
            ], 422);
        }

        $result = $otpService->generateAndSend($phone, 'login', $request->ip());
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Could not send OTP.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully.',
        ]);
    }

    public function verifyLoginOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'identifier' => 'required_without:email|string',
            'email' => 'nullable|string',
            'code' => 'required|digits:6',
        ]);

        $identifier = (string) $request->input('identifier', $request->input('email', ''));
        [$user, $staff] = $this->resolveUserAndStaff($identifier);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with these details.',
            ], 404);
        }

        $phone = $this->resolvePhoneForUser($user, $staff);
        if (!$phone) {
            return response()->json([
                'success' => false,
                'message' => 'No phone number found for this account.',
            ], 422);
        }

        $verify = $otpService->verify($phone, $request->code, 'login');
        if (!$verify['valid']) {
            return response()->json([
                'success' => false,
                'message' => $verify['message'] ?? 'Invalid OTP.',
            ], 422);
        }

        $user->load('roles', 'roles.permissions', 'staff');
        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $this->formatUserForApi($user),
            ],
        ]);
    }

    public function requestPasswordResetEmailLink(Request $request)
    {
        $request->validate([
            'identifier' => 'required_without:email|string',
            'email' => 'nullable|string',
        ]);

        $identifier = (string) $request->input('identifier', $request->input('email', ''));
        [$user, $staff] = $this->resolveUserAndStaff($identifier);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with these details.'], 404);
        }

        $email = strtolower(trim((string) ($user->email ?: ($staff->work_email ?? ''))));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'No valid email found for this account.'], 422);
        }

        $status = Password::sendResetLink(['email' => $email]);
        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json(['success' => false, 'message' => __($status)], 422);
        }

        return response()->json(['success' => true, 'message' => 'Password reset link sent to your email.']);
    }

    public function requestPasswordResetSmsLink(Request $request, SMSService $smsService)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        [$user, $staff] = $this->resolveUserAndStaff((string) $request->identifier);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with these details.'], 404);
        }

        $phone = $this->resolvePhoneForUser($user, $staff);
        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'No phone number found for this account.'], 422);
        }

        $email = strtolower(trim((string) ($user->email ?: ($staff->work_email ?? ''))));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'No valid email found to bind reset token.'], 422);
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $email], true);
        $message = "Password reset link: {$resetUrl} (expires soon). If you did not request this, ignore.";
        $sms = $smsService->sendSMS($phone, $message);

        if (($sms['status'] ?? null) === 'error') {
            return response()->json(['success' => false, 'message' => $sms['message'] ?? 'Failed to send SMS reset link.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Password reset link sent via SMS.']);
    }

    public function requestPasswordResetOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'identifier' => 'required_without:phone|string',
            'phone' => 'nullable|string',
        ]);

        $identifier = (string) $request->input('identifier', $request->input('phone', ''));
        [$user, $staff] = $this->resolveUserAndStaff($identifier);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with these details.'], 404);
        }

        $otpRecipient = filter_var(trim($identifier), FILTER_VALIDATE_EMAIL)
            ? strtolower(trim($identifier))
            : ($this->resolvePhoneForUser($user, $staff) ?? null);

        if (!$otpRecipient) {
            return response()->json(['success' => false, 'message' => 'No OTP destination found for this account.'], 422);
        }

        $result = $otpService->generateAndSend($otpRecipient, 'password_reset', $request->ip());
        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not send OTP.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'OTP sent successfully.']);
    }

    public function verifyPasswordResetOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'identifier' => 'required_without:phone|string',
            'phone' => 'nullable|string',
            'code' => 'required|digits:6',
        ]);

        $identifier = (string) $request->input('identifier', $request->input('phone', ''));
        [$user, $staff] = $this->resolveUserAndStaff($identifier);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with these details.'], 404);
        }

        $otpRecipient = filter_var(trim($identifier), FILTER_VALIDATE_EMAIL)
            ? strtolower(trim($identifier))
            : ($this->resolvePhoneForUser($user, $staff) ?? null);

        if (!$otpRecipient) {
            return response()->json(['success' => false, 'message' => 'No OTP destination found for this account.'], 422);
        }

        $verify = $otpService->verify($otpRecipient, $request->code, 'password_reset');
        if (!$verify['valid']) {
            return response()->json(['success' => false, 'message' => $verify['message'] ?? 'Invalid OTP.'], 422);
        }

        $resetToken = Password::broker()->createToken($user);
        return response()->json([
            'success' => true,
            'data' => [
                'token' => $resetToken,
                'identifier' => $identifier,
            ],
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required_without:email|string',
            'email' => 'nullable|string',
            'token' => 'required|string',
            'password' => 'required|confirmed',
        ]);

        $identifier = (string) $request->input('identifier', $request->input('email', ''));
        [$user, $staff] = $this->resolveUserAndStaff($identifier);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with these details.'], 404);
        }

        $email = strtolower(trim((string) ($user->email ?: ($staff->work_email ?? ''))));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'No valid email found for this account.'], 422);
        }

        $status = Password::reset(
            [
                'email' => $email,
                'token' => (string) $request->token,
                'password' => (string) $request->password,
                'password_confirmation' => (string) $request->password_confirmation,
            ],
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['success' => false, 'message' => __($status)], 422);
        }

        return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
    }

    /**
     * Get current user (profile) - for auth:sanctum.
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load('roles', 'roles.permissions', 'staff');

        return response()->json([
            'success' => true,
            'data' => $this->formatUserForApi($user),
        ]);
    }

    /**
     * Logout - revoke current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Format user for API response (matches mobile app User type).
     */
    protected function formatUserForApi(User $user): array
    {
        $roleName = $user->roles->first()?->name ?? 'Teacher';
        $permissions = $user->getAllPermissions()->pluck('name')->values()->toArray();

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleName,
            'permissions' => $permissions,
        ];

        $staff = $user->staff;
        if ($staff) {
            $data['staff_id'] = $staff->id;
            if (!empty($staff->phone_number)) {
                $data['phone'] = $staff->phone_number;
            }
            if (in_array(strtolower($roleName), ['teacher', 'senior teacher', 'supervisor'])) {
                $data['teacher_id'] = $staff->id;
            }
            $data['avatar'] = $staff->photo_url ?: null;
        }

        // Parent: User has parent_id -> parent_info
        if ($user->parent_id) {
            $data['parent_id'] = $user->parent_id;
        }

        // Student: linked via parent_id on students table (students.parent_id -> parent_info)
        // No user_id column on students; student users are rare - skip student_id if schema lacks it

        return $data;
    }

    protected function resolveUserAndStaff(string $identifier): array
    {
        $raw = trim($identifier);
        $email = strtolower($raw);
        $user = null;
        $staff = null;

        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
            if (!$user) {
                $staff = Staff::whereNotNull('work_email')
                    ->whereRaw('LOWER(TRIM(work_email)) = ?', [$email])
                    ->first();
                if ($staff && $staff->user_id) {
                    $user = User::find($staff->user_id);
                }
            }
        } else {
            $normalized = $this->normalizePhone($raw);
            $digits = ltrim($normalized, '+');
            $variants = array_unique(array_filter([
                $raw,
                $normalized,
                $digits,
                str_starts_with($digits, '254') ? '0' . substr($digits, 3) : null,
            ]));

            $staff = Staff::whereNotNull('phone_number')
                ->whereIn('phone_number', $variants)
                ->first();

            if (!$staff) {
                $staff = Staff::whereNotNull('phone_number')
                    ->where(function ($q) use ($digits, $normalized) {
                        $q->where('phone_number', 'like', '%' . $digits . '%')
                            ->orWhere('phone_number', 'like', '%' . $normalized . '%');
                    })
                    ->first();
            }

            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
        }

        if ($user && !$staff) {
            $staff = Staff::where('user_id', $user->id)->first();
        }

        return [$user, $staff];
    }

    protected function resolvePhoneForUser(User $user, ?Staff $staff): ?string
    {
        if ($staff && !empty($staff->phone_number)) {
            return $staff->phone_number;
        }

        if (Schema::hasColumn('users', 'phone_number') && !empty($user->phone_number)) {
            return (string) $user->phone_number;
        }

        return null;
    }

    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '0')) {
                return '+254' . substr($phone, 1);
            }
            return '+' . $phone;
        }
        return $phone;
    }
}
