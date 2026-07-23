<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * First-time parent account claim (self-service signup).
 *
 * Flow:
 *   1. requestOtp       — send OTP to phone or email (purpose parent_claim)
 *   2. verifyOtp        — verify OTP, issue a short-lived claim session token
 *   3. verifyAdmission  — bind verified contact to a student's parent_info via admission number
 *   4. complete         — create/link the parent user and issue a Sanctum login token
 *
 * Security notes:
 *   - We never disclose whether a phone/email exists before OTP + admission match.
 *   - The verified contact MUST match the student's parent_info (father/mother/guardian).
 *   - Claim session lives in the cache for 30 minutes only.
 */
class ApiParentClaimController extends Controller
{
    private const CLAIM_TTL_MINUTES = 30;
    private const CACHE_PREFIX = 'parent_claim:';

    /**
     * POST /parent-claim/otp/request
     * Body: { channel: phone|email, identifier }
     */
    public function requestOtp(Request $request, OtpService $otpService)
    {
        $validated = $request->validate([
            'channel' => 'required|in:phone,email',
            'identifier' => 'required|string|max:190',
        ]);

        $channel = $validated['channel'];
        $identifier = trim($validated['identifier']);

        if ($channel === 'email' && !filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ], 422);
        }
        if ($channel === 'phone' && !preg_match('/^\+?[0-9 \-()]{7,20}$/', $identifier)) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number.',
            ], 422);
        }

        // Rate limit: max 5 requests per identifier per 10 minutes, and 15 per IP per 10 min.
        $normalizedKey = normalize_contact_for_parent_match($identifier) ?: $identifier;
        $idKey = 'parent-claim-otp:' . sha1(strtolower($normalizedKey));
        $ipKey = 'parent-claim-otp-ip:' . sha1((string) $request->ip());

        if (RateLimiter::tooManyAttempts($idKey, 5) || RateLimiter::tooManyAttempts($ipKey, 15)) {
            $seconds = max(
                RateLimiter::availableIn($idKey),
                RateLimiter::availableIn($ipKey)
            );
            return response()->json([
                'success' => false,
                'message' => "Too many attempts. Please try again in {$seconds} seconds.",
            ], 429);
        }

        RateLimiter::hit($idKey, 600);
        RateLimiter::hit($ipKey, 600);

        $result = $otpService->generateAndSend($identifier, 'parent_claim', $request->ip());
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Could not send verification code.',
            ], 422);
        }

        // Do NOT reveal whether the contact matches a parent — always return a neutral success.
        return response()->json([
            'success' => true,
            'message' => 'If the details are valid, a verification code has been sent.',
        ]);
    }

    /**
     * POST /parent-claim/otp/verify
     * Body: { channel, identifier, code } → { claim_token }
     */
    public function verifyOtp(Request $request, OtpService $otpService)
    {
        $validated = $request->validate([
            'channel' => 'required|in:phone,email',
            'identifier' => 'required|string|max:190',
            'code' => 'required|digits:6',
        ]);

        $channel = $validated['channel'];
        $identifier = trim($validated['identifier']);

        $verify = $otpService->verify($identifier, $validated['code'], 'parent_claim');
        if (!$verify['valid']) {
            return response()->json([
                'success' => false,
                'message' => $verify['message'] ?? 'Invalid or expired verification code.',
            ], 422);
        }

        $claimToken = Str::random(64);
        Cache::put(self::CACHE_PREFIX . $claimToken, [
            'channel' => $channel,
            'identifier' => $identifier,
            'normalized' => normalize_contact_for_parent_match($identifier),
            'verified_at' => now()->toIso8601String(),
            'admission_verified' => false,
            'parent_info_id' => null,
        ], now()->addMinutes(self::CLAIM_TTL_MINUTES));

        return response()->json([
            'success' => true,
            'data' => [
                'claim_token' => $claimToken,
                'expires_in' => self::CLAIM_TTL_MINUTES * 60,
            ],
        ]);
    }

    /**
     * POST /parent-claim/verify-admission
     * Body: { claim_token, admission_number }
     * → { children: [{ id, first_name_masked, class_name, admission_number }], parent_info_id }
     */
    public function verifyAdmission(Request $request)
    {
        $validated = $request->validate([
            'claim_token' => 'required|string',
            'admission_number' => 'required|string|max:100',
        ]);

        $session = $this->getClaimSession($validated['claim_token']);
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Your verification session has expired. Please start again.',
            ], 422);
        }

        $admission = trim($validated['admission_number']);
        $student = Student::where('admission_number', $admission)
            ->where('archive', 0)
            ->with('parent')
            ->first();

        // Generic error — do not reveal whether the admission number exists.
        $noMatch = response()->json([
            'success' => false,
            'message' => 'We could not match these details. Please check the admission number and that you used the contact registered with the school.',
        ], 422);

        if (!$student || !$student->parent) {
            return $noMatch;
        }

        $parent = $student->parent;
        if (!$this->contactMatchesParent($session['channel'], $session['normalized'], $parent)) {
            Log::info('Parent claim: contact did not match parent_info', [
                'admission' => $admission,
                'channel' => $session['channel'],
            ]);
            return $noMatch;
        }

        // Build the list of children under this parent (direct + siblings via family).
        $children = $this->childrenForParent($parent, $student);

        // Persist the verified admission match into the claim session.
        Cache::put(self::CACHE_PREFIX . $validated['claim_token'], array_merge($session, [
            'admission_verified' => true,
            'parent_info_id' => $parent->id,
        ]), now()->addMinutes(self::CLAIM_TTL_MINUTES));

        return response()->json([
            'success' => true,
            'data' => [
                'children' => $children,
                'parent_info_id' => $parent->id,
            ],
        ]);
    }

    /**
     * POST /parent-claim/complete
     * Body: { claim_token, name, password, password_confirmation, email? }
     * → { token, user, expires_at }
     */
    public function complete(Request $request)
    {
        $validated = $request->validate([
            'claim_token' => 'required|string',
            'name' => 'required|string|max:190',
            'password' => 'required|string|min:8|confirmed',
            'email' => 'nullable|email|max:190',
        ]);

        $session = $this->getClaimSession($validated['claim_token']);
        if (!$session || !($session['admission_verified'] ?? false) || empty($session['parent_info_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your child\'s admission number before creating an account.',
            ], 422);
        }

        $parent = ParentInfo::find($session['parent_info_id']);
        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'This parent record is no longer available. Please contact the school.',
            ], 422);
        }

        $channel = $session['channel'];
        $identifier = $session['identifier'];
        $email = $channel === 'email'
            ? strtolower(trim($identifier))
            : (isset($validated['email']) ? strtolower(trim($validated['email'])) : null);
        $phone = $channel === 'phone' ? $this->normalizePhone($identifier) : null;

        // Detect an existing user by the verified contact.
        $existingUser = $this->findExistingUser($channel, $identifier);

        if ($existingUser && $existingUser->parent_id) {
            return response()->json([
                'success' => false,
                'message' => 'An account already exists for these details. Please sign in instead.',
            ], 409);
        }

        try {
            $user = DB::transaction(function () use ($existingUser, $parent, $validated, $email, $phone) {
                if ($existingUser) {
                    // Staff (or other) user claiming a parent identity: link parent_id, keep existing roles.
                    $existingUser->parent_id = $parent->id;
                    $existingUser->parent_profile_review_required = true;
                    if ($phone && empty($existingUser->phone_number)) {
                        $existingUser->phone_number = $phone;
                    }
                    $existingUser->save();
                    $this->assignParentRole($existingUser);
                    return $existingUser;
                }

                $user = new User();
                $user->name = $validated['name'];
                $user->email = $email;
                $user->phone_number = $phone;
                $user->password = Hash::make($validated['password']);
                $user->parent_id = $parent->id;
                $user->parent_profile_review_required = true;
                $user->save();
                $this->assignParentRole($user);
                return $user;
            });
        } catch (\Throwable $e) {
            Log::error('Parent claim: failed to create/link user', [
                'error' => $e->getMessage(),
                'parent_info_id' => $parent->id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not complete signup. Please try again.',
            ], 500);
        }

        // One-time use: invalidate the claim session now that the account exists.
        Cache::forget(self::CACHE_PREFIX . $validated['claim_token']);

        $user->load('roles', 'roles.permissions', 'staff');
        $expiresAt = now()->addDays(7);
        $token = $user->createToken('mobile-app', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => app(AuthApiController::class)->formatUserForApiPublic($user),
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    /** Load and validate a claim session from the cache. */
    private function getClaimSession(string $claimToken): ?array
    {
        $session = Cache::get(self::CACHE_PREFIX . $claimToken);
        return is_array($session) ? $session : null;
    }

    /** Whether the verified contact matches any parent_info father/mother/guardian slot. */
    private function contactMatchesParent(string $channel, string $normalizedNeedle, ParentInfo $parent): bool
    {
        if ($normalizedNeedle === '') {
            return false;
        }

        $fields = $channel === 'email'
            ? ['father_email', 'mother_email', 'guardian_email']
            : ['father_phone', 'mother_phone', 'guardian_phone', 'father_whatsapp', 'mother_whatsapp', 'guardian_whatsapp'];

        foreach ($fields as $field) {
            $candidate = normalize_contact_for_parent_match($parent->{$field} ?? '');
            if ($candidate !== '' && $candidate === $normalizedNeedle) {
                return true;
            }
        }

        return false;
    }

    /**
     * Masked list of children under this parent (direct + siblings via family).
     *
     * @return array<int, array{id:int, first_name_masked:string, class_name:?string, admission_number:?string}>
     */
    private function childrenForParent(ParentInfo $parent, Student $matched): array
    {
        $directIds = Student::where('parent_id', $parent->id)->where('archive', 0)->pluck('id');
        $familyIds = Student::where('parent_id', $parent->id)
            ->whereNotNull('family_id')
            ->pluck('family_id')
            ->unique()
            ->filter();

        $query = Student::query()->where('archive', 0)->with('classroom');
        if ($familyIds->isNotEmpty()) {
            $query->where(function ($q) use ($directIds, $familyIds) {
                $q->whereIn('id', $directIds)->orWhereIn('family_id', $familyIds);
            });
        } else {
            $query->whereIn('id', $directIds);
        }

        return $query->get()->map(function (Student $s) {
            return [
                'id' => (int) $s->id,
                'first_name_masked' => $this->maskName($s->first_name),
                'class_name' => $s->classroom?->name,
                'admission_number' => $s->admission_number,
            ];
        })->values()->all();
    }

    private function maskName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '***';
        }
        $first = mb_substr($name, 0, 1);
        return $first . str_repeat('*', max(2, mb_strlen($name) - 1));
    }

    /** Find an existing user matching the verified contact (email or phone/staff phone). */
    private function findExistingUser(string $channel, string $identifier): ?User
    {
        if ($channel === 'email') {
            $email = strtolower(trim($identifier));
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
            if ($user) {
                return $user;
            }
            $staff = Staff::whereNotNull('work_email')
                ->whereRaw('LOWER(TRIM(work_email)) = ?', [$email])
                ->first();
            return $staff && $staff->user_id ? User::find($staff->user_id) : null;
        }

        $normalized = $this->normalizePhone($identifier);
        $digits = ltrim($normalized, '+');
        $variants = array_values(array_unique(array_filter([
            $identifier,
            $normalized,
            $digits,
            str_starts_with($digits, '254') ? '0' . substr($digits, 3) : null,
        ])));

        $user = User::query()
            ->when(true, function ($q) use ($variants) {
                $q->whereIn('phone_number', $variants);
            })
            ->first();
        if ($user) {
            return $user;
        }

        $staff = Staff::whereNotNull('phone_number')->whereIn('phone_number', $variants)->first();
        return $staff && $staff->user_id ? User::find($staff->user_id) : null;
    }

    private function assignParentRole(User $user): void
    {
        try {
            $guard = config('auth.defaults.guard', 'web');
            Role::firstOrCreate(['name' => 'Parent', 'guard_name' => $guard]);
            if (!$user->hasRole('Parent')) {
                $user->assignRole('Parent');
            }
        } catch (\Throwable $e) {
            Log::warning('Parent claim: could not assign Parent role', ['error' => $e->getMessage()]);
        }
    }

    private function normalizePhone(string $phone): string
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
