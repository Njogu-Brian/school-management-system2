<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = auth()->user();

            // If you use Spatie roles:
            if ($user->hasRole('admin'))   { return redirect()->route('admin.dashboard'); }
            if ($user->hasRole('teacher')) { return redirect()->route('teacher.dashboard'); }
            if ($user->hasRole('student')) { return redirect()->route('student.dashboard'); }

            // Fallback to /home (your role-based router)
            return redirect()->route('home');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Email address and password are incorrect.');
    }

    protected function loggedOut(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }
}
