<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        return Socialite::driver('google')
            ->stateless()
            ->with([
                'prompt' => 'select_account',
            ])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable) {
            return redirect()->route('login')->withErrors(['identifier' => 'Google sign-in failed. Please try again.']);
        }

        $googleId = (string) ($googleUser->getId() ?? '');
        $googleEmail = strtolower(trim((string) ($googleUser->getEmail() ?? '')));
        if ($googleId === '' || $googleEmail === '') {
            return redirect()->route('login')->withErrors(['identifier' => 'Google did not return an email address.']);
        }

        // If this Google account is already linked, login directly.
        $linked = User::where('google_id', $googleId)->first();
        if ($linked) {
            Auth::login($linked, true);
            return redirect()->route('home');
        }

        // If a local account exists for this email, link then login.
        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$googleEmail])->first();
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'No account found for this Google email. Please sign in with password/OTP first, then link Google in your profile.',
            ]);
        }

        $user->forceFill([
            'google_id' => $googleId,
            'google_email' => $googleEmail,
        ])->save();

        Auth::login($user, true);
        return redirect()->route('home')->with('status', 'Google account linked. You can now sign in with Google.');
    }
}

