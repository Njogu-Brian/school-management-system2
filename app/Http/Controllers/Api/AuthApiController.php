<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\SMSService;
use App\Services\OtpService;
use App\Services\ParentCredentialsService;
use App\Services\LoginIdentifierService;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
        $password = (string) $request->password;
        $ids = app(LoginIdentifierService::class);
        $credentials = app(ParentCredentialsService::class);
        [$user] = $ids->findUserAndStaff($identifier);

        if (! $user) {
            $match = $ids->findParentSlotByContact($identifier);
            if ($match && $credentials->plainMatchesFormula($match['parent'], $password)) {
                try {
                    $user = $credentials->ensureParentUserForSlot($match['parent'], $match['slot']);
                } catch (\Throwable $e) {
                    $user = null;
                }
            }
        }

        if (!$user || ! $credentials->passwordIsValid($user, $password)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $user->load('roles', 'roles.permissions', 'staff');

        return $this->respondWithToken($user);
    }

    /**
     * PIN unlock — same PIN works on any device once it has been saved on the account.
     */
    public function loginWithPin(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'pin' => ['required', 'string', 'regex:/^\d{4,6}$/'],
        ]);

        $identifier = (string) $request->input('identifier');
        $pin = (string) $request->input('pin');
        $failKey = 'login_pin_fail:'.sha1(mb_strtolower($identifier).'|'.$request->ip());
        $fails = (int) Cache::get($failKey, 0);
        if ($fails >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Too many PIN attempts. Sign in with your password, then try again later.',
            ], 429);
        }

        $ids = app(LoginIdentifierService::class);
        [$user] = $ids->findUserAndStaff($identifier);
        $hash = $user && Schema::hasColumn('users', 'unlock_pin_hash')
            ? (string) ($user->unlock_pin_hash ?? '')
            : '';

        if (! $user || $hash === '' || ! Hash::check($pin, $hash)) {
            Cache::put($failKey, $fails + 1, now()->addMinutes(15));
            return response()->json([
                'success' => false,
                'message' => 'The provided PIN is incorrect.',
            ], 401);
        }

        Cache::forget($failKey);
        $user->load('roles', 'roles.permissions', 'staff');

        return $this->respondWithToken($user);
    }

    /**
     * Device-bound biometric unlock. The Face ID / fingerprint never leaves the phone;
     * a keychain secret registered on this device issues a fresh session.
     */
    public function loginWithBiometric(Request $request)
    {
        $request->validate([
            'selector' => ['required', 'string', 'min:16', 'max:64'],
            'secret' => ['required', 'string', 'min:32', 'max:128'],
        ]);

        if (! Schema::hasTable('user_biometric_unlocks')) {
            return response()->json([
                'success' => false,
                'message' => 'Biometric unlock is not available yet.',
            ], 503);
        }

        $selector = (string) $request->input('selector');
        $secret = (string) $request->input('secret');
        $failKey = 'login_bio_fail:'.sha1($selector.'|'.$request->ip());
        $fails = (int) Cache::get($failKey, 0);
        if ($fails >= 8) {
            return response()->json([
                'success' => false,
                'message' => 'Too many biometric attempts. Sign in with your password or PIN.',
            ], 429);
        }

        $row = \App\Models\UserBiometricUnlock::query()->where('selector', $selector)->first();
        if (! $row || ! Hash::check($secret, (string) $row->secret_hash)) {
            Cache::put($failKey, $fails + 1, now()->addMinutes(15));
            return response()->json([
                'success' => false,
                'message' => 'Biometric unlock is not valid on this device.',
            ], 401);
        }

        Cache::forget($failKey);
        $row->forceFill(['last_used_at' => now()])->save();
        $user = User::query()->find($row->user_id);
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 401);
        }

        $user->load('roles', 'roles.permissions', 'staff');

        return $this->respondWithToken($user);
    }

    /**
     * Login with Google ID token (mobile app).
     * Links existing account by email if not linked yet.
     */
    public function loginWithGoogle(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $tokenInfo = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => (string) $request->id_token,
        ]);

        if (! $tokenInfo->ok()) {
            return response()->json(['success' => false, 'message' => 'Invalid Google token.'], 401);
        }

        $payload = $tokenInfo->json();
        $aud = (string) ($payload['aud'] ?? '');
        $googleId = (string) ($payload['sub'] ?? '');
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $emailVerified = (string) ($payload['email_verified'] ?? 'false');

        $expectedAud = (string) config('services.google.client_id');
        if ($expectedAud !== '' && $aud !== $expectedAud) {
            return response()->json(['success' => false, 'message' => 'Google token audience mismatch.'], 401);
        }
        if ($googleId === '' || $email === '' || $emailVerified !== 'true') {
            return response()->json(['success' => false, 'message' => 'Google account email must be verified.'], 401);
        }

        $user = User::where('google_id', $googleId)->first();
        if (! $user) {
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
            if (! $user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found for this Google email. Please sign in with password/OTP first.',
                ], 404);
            }

            $user->forceFill([
                'google_id' => $googleId,
                'google_email' => $email,
            ])->save();
        }

        $user->load('roles', 'roles.permissions', 'staff');

        return $this->respondWithToken($user);
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

        return $this->respondWithToken($user);
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

        $resetToken = Str::random(64);
        Cache::put('pwd_reset:'.$resetToken, $user->id, now()->addMinutes(20));

        $laravelToken = null;
        try {
            $laravelToken = Password::broker()->createToken($user);
        } catch (\Throwable $e) {
            $laravelToken = $resetToken;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $resetToken,
                'legacy_token' => $laravelToken,
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
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
        ]);

        $identifier = (string) $request->input('identifier', $request->input('email', ''));
        [$user, $staff] = $this->resolveUserAndStaff($identifier);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No account found with these details.'], 404);
        }

        $cacheUserId = Cache::pull('pwd_reset:'.(string) $request->token);
        if ($cacheUserId && (int) $cacheUserId === (int) $user->id) {
            $this->applyResetPassword($user, (string) $request->password);

            return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
        }

        $email = strtolower(trim((string) ($user->email ?: ($staff->work_email ?? ''))));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Reset code expired. Request a new OTP.'], 422);
        }

        $status = Password::reset(
            [
                'email' => $email,
                'token' => (string) $request->token,
                'password' => (string) $request->password,
                'password_confirmation' => (string) $request->password_confirmation,
            ],
            function ($resetUser, $password) {
                $this->applyResetPassword($resetUser, $password);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['success' => false, 'message' => __($status)], 422);
        }

        return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
    }

    protected function applyResetPassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();
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
     * Public wrapper for formatUserForApi (used by the parent-claim flow).
     */
    public function formatUserForApiPublic(User $user): array
    {
        return $this->formatUserForApi($user);
    }

    /**
     * Format user for API response (matches mobile app User type).
     */
    protected function formatUserForApi(User $user): array
    {
        // Prefer a staff/work role when the user also has Parent (dual identity).
        // Otherwise Spatie's roles->first() order is undefined and Work mode can break.
        $roleName = $this->resolvePrimaryRoleName($user);
        $permissions = $user->getAllPermissions()->pluck('name')->values()->toArray();

        $displayName = trim((string) $user->name);
        if (empty($user->parent_id) && ! $user->hasAnyRole([
            'Super Admin', 'Admin', 'Director', 'Academic Administrator',
            'Secretary', 'Accountant', 'Finance Officer',
        ])) {
            $this->attachParentIdFromContact($user);
        }
        $identityGate = null;
        if ($user->parent_id) {
            $identityGate = app(ParentCredentialsService::class)->identityGateForUser($user);
            if (app(ParentCredentialsService::class)->identityNameIsPlaceholder($displayName)) {
                $slotName = trim((string) ($identityGate['parent']['name'] ?? ''));
                $displayName = $slotName !== '' ? $slotName : 'Parent';
            }
        }

        $data = [
            'id' => $user->id,
            'name' => $displayName !== '' ? $displayName : $user->name,
            'email' => $user->email,
            'role' => $roleName,
            'permissions' => $permissions,
        ];
        if ($identityGate !== null) {
            $data['identity_gate_required'] = (bool) ($identityGate['required'] ?? false);
        }

        $staff = $user->staff;
        if ($staff) {
            $data['staff_id'] = $staff->id;
            if (!empty($staff->phone_number)) {
                $data['phone'] = $staff->phone_number;
            }
            if (
                $user->hasTeacherLikeRole()
                || in_array(strtolower($roleName), ['teacher', 'senior teacher', 'deputy senior teacher', 'supervisor'], true)
            ) {
                $data['teacher_id'] = $staff->id;
                $data['class_teacher_classroom_ids'] = $user->getClassTeacherClassroomIds();
                $data['assigned_classroom_ids'] = $user->getAssignedClassroomIds();
                $data['assigned_subject_ids'] = $user->getAssignedSubjectIds();
                $data['is_homeroom_teacher'] = $user->isHomeroomTeacher();
                $data['is_subject_teacher_only'] = $user->isSubjectTeacherOnly();
                $data['can_mark_class_attendance'] = $user->isHomeroomTeacher()
                    || $user->isSeniorTeacherUser()
                    || $user->isDeputySeniorTeacherUser();
                $data['can_view_student_profiles'] = $user->isHomeroomTeacher()
                    || $user->isSeniorTeacherUser()
                    || $user->isDeputySeniorTeacherUser();
            }
            $data['avatar'] = $staff->photo_url ?: null;
        }

        // Fall back to the user's own phone number when no staff record carries one.
        if (empty($data['phone']) && Schema::hasColumn('users', 'phone_number') && !empty($user->phone_number)) {
            $data['phone'] = (string) $user->phone_number;
        }

        if ($user->parent_id) {
            $data['parent_id'] = $user->parent_id;
        }

        // Dual-identity / mode flags for the mobile Work|Home switcher.
        $hasParent = ! empty($user->parent_id) || $user->hasAnyRole(['Parent', 'Guardian']);
        $hasStaff = $staff !== null;
        $data['can_home_mode'] = $hasParent;
        $data['can_work_mode'] = $hasStaff
            || $user->hasAnyRole([
                'Director', 'Super Admin', 'Admin', 'Secretary', 'Accountant', 'Finance Officer',
                'Academic Administrator',
            ]);

        if (Schema::hasColumn('users', 'parent_profile_review_required')) {
            $data['parent_profile_review_required'] = (bool) $user->parent_profile_review_required;
        }

        if (Schema::hasColumn('users', 'must_change_password')) {
            $data['must_change_password'] = (bool) $user->must_change_password;
        }

        // Student: linked via parent_id on students table (students.parent_id -> parent_info)
        // No user_id column on students; student users are rare - skip student_id if schema lacks it

        return $data;
    }

    /**
     * Pick the role string the apps use for shell selection.
     * Staff/admin + Parent dual accounts must surface the work role so Admin/Work mode works.
     */
    protected function resolvePrimaryRoleName(User $user): string
    {
        $roles = $user->getRoleNames()->map(fn ($n) => (string) $n)->values();
        if ($roles->isEmpty()) {
            return 'Teacher';
        }

        $parentLike = ['parent', 'guardian'];
        $staffPreferred = [
            'Director', 'Super Admin', 'Admin', 'Secretary', 'Accountant', 'Finance Officer',
            'Academic Administrator', 'Senior Teacher', 'Deputy Senior Teacher',
            'Supervisor', 'Teacher', 'Driver', 'Transport',
        ];

        // Always prefer work/admin roles over Parent when the account is dual-linked.
        foreach ($staffPreferred as $preferred) {
            $match = $roles->first(fn ($r) => strcasecmp($r, $preferred) === 0);
            if ($match) {
                return $match;
            }
        }

        $nonParent = $roles->first(fn ($r) => ! in_array(strtolower($r), $parentLike, true));
        if ($nonParent) {
            return $nonParent;
        }

        return $roles->first() ?? 'Teacher';
    }

    protected function resolveUserAndStaff(string $identifier): array
    {
        return app(LoginIdentifierService::class)->findUserAndStaff($identifier);
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
        return app(LoginIdentifierService::class)->normalizePhone($phone);
    }

    /**
     * Keep other device sessions (biometric / PIN) alive. Only prune expired tokens.
     */
    protected function respondWithToken(User $user)
    {
        $user->tokens()->where('expires_at', '<', now())->delete();

        $expiresAt = now()->addDays(7);
        $token = $user->createToken('mobile-app', ['*'], $expiresAt)->plainTextToken;
        $user->markAppLogin();

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $this->formatUserForApi($user),
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    /**
     * Teachers who are also parents often have a staff login but a missing users.parent_id.
     */
    protected function attachParentIdFromContact(User $user): void
    {
        if (! Schema::hasColumn('users', 'parent_id')) {
            return;
        }

        $contacts = array_filter([
            Schema::hasColumn('users', 'phone_number') ? (string) ($user->phone_number ?? '') : '',
            (string) ($user->email ?? ''),
            (string) ($user->staff?->phone_number ?? ''),
            (string) ($user->staff?->work_email ?? ''),
        ]);

        $ids = app(LoginIdentifierService::class);
        foreach ($contacts as $contact) {
            $match = $ids->findParentSlotByContact($contact);
            if (! $match) {
                continue;
            }
            $user->parent_id = $match['parent']->id;
            $user->saveQuietly();
            break;
        }
    }
}
