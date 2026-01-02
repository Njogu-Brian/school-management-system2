<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // ✅ Ensure this is included
use App\Models\Setting;
use App\Models\Announcement;
use App\Services\OtpService;
use Illuminate\Support\Facades\Log;

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
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Normalize email - trim whitespace and convert to lowercase
        $email = strtolower(trim($credentials['email']));
        
        // Check if user exists - case-insensitive email matching with trimmed comparison
        // Use LIKE to find potential matches (handles whitespace), then verify
        $potentialUsers = User::where('email', 'like', '%' . $email . '%')
            ->orWhere('email', 'like', '%' . trim($credentials['email']) . '%')
            ->get();
        
        $user = $potentialUsers->first(function($u) use ($email) {
            return $u->email && strtolower(trim($u->email)) === $email;
        });
        
        // If not found in users table, check if staff exists with this work_email
        if (!$user) {
            $potentialStaff = \App\Models\Staff::whereNotNull('work_email')
                ->where(function($q) use ($email, $credentials) {
                    $q->where('work_email', 'like', '%' . $email . '%')
                      ->orWhere('work_email', 'like', '%' . trim($credentials['email']) . '%');
                })
                ->get();
            
            $staff = $potentialStaff->first(function($s) use ($email) {
                return $s->work_email && strtolower(trim($s->work_email)) === $email;
            });
            
            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
        }
        
        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email address.']);
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

        return back()->withErrors(['email' => 'Invalid password. Please check your password and try again.']);
    }

    /**
     * Request OTP for login
     */
    protected function requestOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $email = strtolower(trim($request->email));
        
        // Find user
        $user = User::where('email', 'like', '%' . $email . '%')
            ->get()
            ->first(function($u) use ($email) {
                return $u->email && strtolower(trim($u->email)) === $email;
            });

        $staff = null;
        if (!$user) {
            // Check staff
            $staff = \App\Models\Staff::whereNotNull('work_email')
                ->get()
                ->first(function($s) use ($email) {
                    return $s->work_email && strtolower(trim($s->work_email)) === $email;
                });
            
            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
        } else {
            // User found directly, but check if they have associated staff record
            $staff = \App\Models\Staff::where('user_id', $user->id)->first();
        }

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email address.']);
        }

        // Get phone number from staff (User model doesn't have phone_number)
        $phone = null;
        if ($staff && $staff->phone_number) {
            $phone = $staff->phone_number;
        }

        if (!$phone) {
            return back()->withErrors(['email' => 'No phone number found for this account. Please use password login.']);
        }

        // Generate and send OTP
        $otpService = app(OtpService::class);
        $result = $otpService->generateAndSend($phone, 'login', $request->ip());

        if (!$result['success']) {
            return back()->withErrors(['email' => $result['message'] . ' Please use password login instead.']);
        }

        return back()->with([
            'otp_sent' => true,
            'otp_phone' => substr($phone, -4), // Show last 4 digits
            'otp_email' => $email
        ]);
    }

    /**
     * Login with OTP
     */
    protected function loginWithOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|digits:6'
        ]);

        $email = strtolower(trim($request->email));
        
        // Find user
        $user = User::where('email', 'like', '%' . $email . '%')
            ->get()
            ->first(function($u) use ($email) {
                return $u->email && strtolower(trim($u->email)) === $email;
            });

        $staff = null;
        if (!$user) {
            // Check staff
            $staff = \App\Models\Staff::whereNotNull('work_email')
                ->get()
                ->first(function($s) use ($email) {
                    return $s->work_email && strtolower(trim($s->work_email)) === $email;
                });
            
            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
        } else {
            // User found directly, but check if they have associated staff record
            $staff = \App\Models\Staff::where('user_id', $user->id)->first();
        }

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email address.']);
        }

        // Get phone number from staff (User model doesn't have phone_number)
        $phone = null;
        if ($staff && $staff->phone_number) {
            $phone = $staff->phone_number;
        } else {
            // If staff not found yet, try to find it by user_id
            if ($user && !$staff) {
                $staff = \App\Models\Staff::where('user_id', $user->id)->first();
                if ($staff && $staff->phone_number) {
                    $phone = $staff->phone_number;
                }
            }
        }

        if (!$phone) {
            Log::warning('OTP verification: No phone number found', [
                'user_id' => $user->id ?? null,
                'email' => $email,
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
            'email' => $email,
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
                ->withInput(['email' => $email, 'otp_sent' => true]);
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
        $request->validate(['email' => 'required|email']);

        // Check if OTP reset is requested
        if ($request->has('use_otp')) {
            return $this->requestPasswordResetOTP($request);
        }

        // Standard email reset link
        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    /**
     * Request OTP for password reset
     */
    protected function requestPasswordResetOTP(Request $request)
    {
        $email = strtolower(trim($request->email));
        
        // Find user
        $user = User::where('email', 'like', '%' . $email . '%')
            ->get()
            ->first(function($u) use ($email) {
                return $u->email && strtolower(trim($u->email)) === $email;
            });

        if (!$user) {
            // Check staff
            $staff = \App\Models\Staff::whereNotNull('work_email')
                ->get()
                ->first(function($s) use ($email) {
                    return $s->work_email && strtolower(trim($s->work_email)) === $email;
                });
            
            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
        }

        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email address.']);
        }

        // Get phone number
        $phone = null;
        if ($user->phone_number) {
            $phone = $user->phone_number;
        } elseif (isset($staff) && $staff->phone_number) {
            $phone = $staff->phone_number;
        }

        if (!$phone) {
            return back()->withErrors(['email' => 'No phone number found. Please use email reset link instead.']);
        }

        // Generate and send OTP
        $otpService = app(OtpService::class);
        $result = $otpService->generateAndSend($phone, 'password_reset', $request->ip());

        if (!$result['success']) {
            return back()->withErrors(['email' => $result['message'] . ' Please use email reset link instead.']);
        }

        // Store email in session for OTP verification
        session(['password_reset_email' => $email, 'password_reset_otp_sent' => true]);

        return redirect()->route('password.reset.otp')
            ->with('status', 'OTP sent to your phone number ending in ' . substr($phone, -4));
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
            'email' => session('password_reset_email')
        ]);
    }

    /**
     * Reset password with OTP
     */
    public function resetWithOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|digits:6',
            'password' => 'required|min:8|confirmed',
        ]);

        $email = strtolower(trim($request->email));
        
        // Find user
        $user = User::where('email', 'like', '%' . $email . '%')
            ->get()
            ->first(function($u) use ($email) {
                return $u->email && strtolower(trim($u->email)) === $email;
            });

        if (!$user) {
            // Check staff
            $staff = \App\Models\Staff::whereNotNull('work_email')
                ->get()
                ->first(function($s) use ($email) {
                    return $s->work_email && strtolower(trim($s->work_email)) === $email;
                });
            
            if ($staff && $staff->user_id) {
                $user = User::find($staff->user_id);
            }
        }

        if (!$user) {
            return back()->withErrors(['email' => 'No account found.']);
        }

        // Get phone number
        $phone = null;
        if ($user->phone_number) {
            $phone = $user->phone_number;
        } elseif (isset($staff) && $staff->phone_number) {
            $phone = $staff->phone_number;
        }

        if (!$phone) {
            return back()->withErrors(['otp_code' => 'No phone number found.']);
        }

        // Verify OTP
        $otpService = app(OtpService::class);
        $result = $otpService->verify($phone, $request->otp_code, 'password_reset');

        if (!$result['valid']) {
            return back()->withErrors(['otp_code' => $result['message']])
                ->withInput(['email' => $email]);
        }

        // Reset password
        $user->forceFill([
            'password' => \Hash::make($request->password)
        ])->save();

        // Clear session
        session()->forget(['password_reset_email', 'password_reset_otp_sent']);

        Log::info('Password reset via OTP', ['user_id' => $user->id, 'email' => $email]);

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
