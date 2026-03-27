<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // ✅ Ensure this is included
use App\Models\Staff;
use App\Models\Setting;
use App\Models\Announcement;
use App\Services\OtpService;
use App\Services\SMSService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        $settings = Setting::all()->keyBy('key');

        $announcements = Announcement::where('active', 1)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->take(5)
            ->pluck('content');

        return view('auth.login', compact('settings', 'announcements'));
    }

    public function login(Request $request)
    {
        // Check if OTP login is requested
        if ($request->has('otp_code')) {
            return $this->loginWithOTP($request);
        }

        // Check if OTP request is being made
        if ($request->has('request_otp')) {
            return $this->requestOTP($request);
        }

        // Standard password login
        $credentials = $request->validate([
            'identifier' => 'required|string',
            'password' => 'required'
        ]);

        [$user] = $this->resolveUserAndStaffByIdentifier($credentials['identifier']);
        
        if (!$user) {
            return back()->withErrors(['identifier' => 'No account found with this email or phone number.']);
        }

        // Use the actual email from the user record for authentication
        $authCredentials = [
            'email' => $user->email,
            'password' => $credentials['password']
        ];

        // Attempt authentication
        if (Auth::attempt($authCredentials, $request->filled('remember'))) {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            $user->load('roles'); // Ensure roles are loaded

            // Check for intended redirect first
            $intended = $request->session()->pull('url.intended');
            if ($intended && str_starts_with($intended, url('/'))) {
                return redirect($intended);
            }

            if ($user->hasRole('admin') || $user->hasRole('Admin') || $user->hasRole('Super Admin')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole('teacher') || $user->hasRole('Teacher')) {
                return redirect()->route('teacher.dashboard');
            } elseif ($user->hasRole('student') || $user->hasRole('Student')) {
                return redirect()->route('student.dashboard');
            }

            return redirect()->route('home'); // fallback
        }

        return back()->withErrors(['identifier' => 'Invalid password. Please check your password and try again.']);
    }

    /**
     * Request OTP for login
     */
    protected function requestOTP(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string'
        ]);

        [$user, $staff, $normalizedIdentifier] = $this->resolveUserAndStaffByIdentifier($request->identifier);

        if (!$user) {
            return back()->withErrors(['identifier' => 'No account found with this email or phone number.']);
        }

        $phone = $this->resolvePhoneFromStaffOrUser($staff, $user);

        if (!$phone) {
            return back()->withErrors(['identifier' => 'No phone number found for this account. Please use password login.']);
        }

        // Generate and send OTP
        $otpService = app(OtpService::class);
        $result = $otpService->generateAndSend($phone, 'login', $request->ip());

        if (!$result['success']) {
            return back()->withErrors(['identifier' => $result['message'] . ' Please use password login instead.']);
        }

        return back()->with([
            'otp_sent' => true,
            'otp_phone' => substr($phone, -4), // Show last 4 digits
            'otp_identifier' => $normalizedIdentifier
        ]);
    }

    /**
     * Login with OTP
     */
    protected function loginWithOTP(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp_code' => 'required|digits:6'
        ]);

        [$user, $staff, $normalizedIdentifier] = $this->resolveUserAndStaffByIdentifier($request->identifier);

        if (!$user) {
            return back()->withErrors(['identifier' => 'No account found with this email or phone number.']);
        }

        $phone = $this->resolvePhoneFromStaffOrUser($staff, $user);

        if (!$phone) {
            Log::warning('OTP verification: No phone number found', [
                'user_id' => $user->id ?? null,
                'identifier' => $normalizedIdentifier,
                'staff_id' => $staff->id ?? null,
                'staff_phone' => $staff->phone_number ?? null
            ]);
            return back()->withErrors(['otp_code' => 'No phone number found. Please use password login.']);
        }

        // Normalize phone number to match how OTP was stored
        // OTP was sent with + prefix, so ensure consistency
        $normalizedPhone = $phone;
        if (!str_starts_with($normalizedPhone, '+')) {
            $normalizedPhone = '+' . ltrim($normalizedPhone, '+');
        }

        Log::info('OTP verification attempt', [
            'identifier' => $normalizedIdentifier,
            'phone_original' => $phone,
            'phone_normalized' => $normalizedPhone,
            'otp_code_length' => strlen($request->otp_code)
        ]);

        // Verify OTP (OtpService will normalize the phone number)
        $otpService = app(OtpService::class);
        $result = $otpService->verify($normalizedPhone, $request->otp_code, 'login');
        
        if (!$result['valid']) {
            Log::warning('OTP verification failed', [
                'phone' => $normalizedPhone,
                'code' => $request->otp_code,
                'message' => $result['message']
            ]);
        }

        if (!$result['valid']) {
            return back()->withErrors(['otp_code' => $result['message']])
                ->withInput(['identifier' => $normalizedIdentifier, 'otp_sent' => true]);
        }

        // Login user
        Auth::login($user, $request->filled('remember'));
        $user->load('roles');

        if ($user->hasRole('admin') || $user->hasRole('Admin') || $user->hasRole('Super Admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('teacher') || $user->hasRole('Teacher')) {
            return redirect()->route('teacher.dashboard');
        } elseif ($user->hasRole('student') || $user->hasRole('Student')) {
            return redirect()->route('student.dashboard');
        }

        return redirect()->route('home');
    }

    protected function resolveUserAndStaffByIdentifier(string $identifier): array
    {
        $raw = trim($identifier);
        $normalized = strtolower($raw);
        $user = null;
        $staff = null;

        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$normalized])->first();
            if (!$user) {
                $staff = Staff::whereNotNull('work_email')
                    ->whereRaw('LOWER(TRIM(work_email)) = ?', [$normalized])
                    ->first();
                if ($staff && $staff->user_id) {
                    $user = User::find($staff->user_id);
                }
            }
        } else {
            $phone = $this->normalizePhone($raw);
            $digits = ltrim($phone, '+');
            $variants = array_unique(array_filter([
                $raw,
                $phone,
                $digits,
                str_starts_with($digits, '254') ? '0' . substr($digits, 3) : null,
            ]));

            $staff = Staff::whereNotNull('phone_number')
                ->whereIn('phone_number', $variants)
                ->first();

            if (!$staff) {
                $staff = Staff::whereNotNull('phone_number')
                    ->where(function ($q) use ($digits, $phone) {
                        $q->where('phone_number', 'like', '%' . $digits . '%')
                            ->orWhere('phone_number', 'like', '%' . $phone . '%');
                    })
                    ->first();
            }

            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
            $normalized = $phone;
        }

        if ($user && !$staff) {
            $staff = Staff::where('user_id', $user->id)->first();
        }

        return [$user, $staff, $normalized];
    }

    protected function resolvePhoneFromStaffOrUser(?Staff $staff, User $user): ?string
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

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }

    /**
     * Show password reset request form
     */
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    /**
     * Send password reset link or OTP
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'method' => 'nullable|in:email_link,sms_link,otp',
            'use_otp' => 'nullable',
        ]);

        $method = $request->input('method');
        if (!$method) {
            $method = $request->has('use_otp') ? 'otp' : 'email_link';
        }

        if ($method === 'otp') {
            return $this->requestPasswordResetOTP($request);
        }

        [$user, $staff, $normalizedIdentifier] = $this->resolveUserAndStaffByIdentifier($request->identifier);
        if (!$user) {
            return back()->withErrors(['identifier' => 'No account found with this email or phone number.']);
        }

        $email = strtolower(trim((string) ($user->email ?: ($staff->work_email ?? ''))));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['identifier' => 'No valid email found for this account.']);
        }

        if ($method === 'email_link') {
            $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $email]);
            return $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
                ? back()->with(['status' => __($status)])
                : back()->withErrors(['identifier' => __($status)]);
        }

        $phone = $this->resolvePhoneFromStaffOrUser($staff, $user);
        if (!$phone) {
            return back()->withErrors(['identifier' => 'No phone number found for this account.']);
        }

        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $email], true);
        $message = "Password reset link: {$resetUrl} (expires soon). If you did not request this, ignore.";

        $smsResult = app(SMSService::class)->sendSMS($phone, $message);
        if (($smsResult['status'] ?? null) === 'error') {
            return back()->withErrors(['identifier' => $smsResult['message'] ?? 'Failed to send SMS reset link.']);
        }

        return back()->with([
            'status' => 'Password reset link sent via SMS to phone ending in ' . substr((string) $phone, -4),
            'last_reset_identifier' => $normalizedIdentifier,
        ]);
    }

    /**
     * Request OTP for password reset
     */
    protected function requestPasswordResetOTP(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifierInput = trim((string) $request->input('identifier', ''));
        [$user, $staff, $normalizedIdentifier] = $this->resolveUserAndStaffByIdentifier($identifierInput);
        if (!$user) {
            return back()->withErrors(['identifier' => 'No account found with this email or phone number.']);
        }

        $otpRecipient = filter_var($normalizedIdentifier, FILTER_VALIDATE_EMAIL)
            ? $normalizedIdentifier
            : ($this->resolvePhoneFromStaffOrUser($staff, $user) ?? null);

        if (!$otpRecipient) {
            return back()->withErrors(['identifier' => 'No phone/email target found for OTP. Use reset link instead.']);
        }

        $otpService = app(OtpService::class);
        $result = $otpService->generateAndSend($otpRecipient, 'password_reset', $request->ip());

        if (!$result['success']) {
            return back()->withErrors(['identifier' => $result['message'] . ' Please use reset link instead.']);
        }

        session([
            'password_reset_identifier' => $normalizedIdentifier,
            'password_reset_otp_sent' => true,
        ]);

        return redirect()->route('password.reset.otp')
            ->with('status', 'OTP sent successfully to your registered contact.');
    }

    /**
     * Show password reset form
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Show OTP password reset form
     */
    public function showOTPResetForm()
    {
        if (!session('password_reset_otp_sent')) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'Please request OTP first.']);
        }

        return view('auth.passwords.reset-otp', [
            'identifier' => session('password_reset_identifier')
        ]);
    }

    /**
     * Reset password with OTP
     */
    public function resetWithOTP(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp_code' => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ]);

        [$user, $staff, $normalizedIdentifier] = $this->resolveUserAndStaffByIdentifier($request->identifier);

        if (!$user) {
            return back()->withErrors(['identifier' => 'No account found.']);
        }

        $otpRecipient = filter_var($normalizedIdentifier, FILTER_VALIDATE_EMAIL)
            ? $normalizedIdentifier
            : ($this->resolvePhoneFromStaffOrUser($staff, $user) ?? null);

        if (!$otpRecipient) {
            return back()->withErrors(['otp_code' => 'No OTP destination found for this account.']);
        }

        // Verify OTP
        $otpService = app(OtpService::class);
        $result = $otpService->verify($otpRecipient, $request->otp_code, 'password_reset');

        if (!$result['valid']) {
            return back()->withErrors(['otp_code' => $result['message']])
                ->withInput(['identifier' => $normalizedIdentifier]);
        }

        // Reset password
        $user->forceFill([
            'password' => \Hash::make($request->password)
        ])->save();

        // Clear session
        session()->forget(['password_reset_identifier', 'password_reset_otp_sent']);

        Log::info('Password reset via OTP', ['user_id' => $user->id, 'identifier' => $normalizedIdentifier]);

        return redirect()->route('login')
            ->with('status', 'Password reset successfully. Please login with your new password.');
    }

    /**
     * Reset password (standard email token method)
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => \Hash::make($password)
                ])->save();
            }
        );

        return $status === \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
