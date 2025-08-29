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

        if (Auth::attempt($credentials)) {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            $user->load('roles'); // Ensure roles are loaded

            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole('teacher')) {
                return redirect()->route('teacher.dashboard');
            } elseif ($user->hasRole('student')) {
                return redirect()->route('student.dashboard');
            }

            return redirect()->route('home'); // fallback
        }

        return back()->withErrors(['email' => 'Invalid credentials']);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
    
}
