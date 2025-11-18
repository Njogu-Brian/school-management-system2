<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // ✅ Ensure this is included
use App\Models\Setting;
use App\Models\Announcement;

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
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Check if user exists
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            return back()->withErrors(['email' => 'No account found with this email address.']);
        }

        // Attempt authentication
        if (Auth::attempt($credentials, $request->filled('remember'))) {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            $user->load('roles'); // Ensure roles are loaded

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

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
    
}
