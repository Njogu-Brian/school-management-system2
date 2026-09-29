<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleAccountService;
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

        $resolved = app(GoogleAccountService::class)->resolveUserForLogin($googleId, $googleEmail);
        if (! ($resolved['ok'] ?? false)) {
            return redirect()->route('login')->withErrors([
                'identifier' => $resolved['message'] ?? 'No account found for this Google email.',
            ]);
        }

        /** @var User $user */
        $user = $resolved['user'];
        Auth::login($user, true);

        return $this->afterSocialLogin($user, 'Signed in with Google.');
    }

    protected function afterSocialLogin(User $user, ?string $status = null): RedirectResponse
    {
        $user->loadMissing('roles');
        if ($user->mustUseMobileApp()) {
            return \App\Support\ParentWebPortalGate::reject($user);
        }

        $redirect = redirect()->route('home');

        return $status ? $redirect->with('status', $status) : $redirect;
    }
}
