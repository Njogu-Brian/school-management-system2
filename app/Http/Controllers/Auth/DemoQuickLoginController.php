<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoQuickLoginController extends Controller
{
    public function loginAs(Request $request, string $persona): RedirectResponse
    {
        if (! config('demo.quick_login_enabled')) {
            abort(404);
        }

        $config = config('demo.personas.'.$persona);
        if (! is_array($config)) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Unknown demo persona.',
            ]);
        }

        $user = $this->resolvePersonaUser($config);
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'No demo account is available for '.$config['label'].'.',
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Auth::login($user, true);
        $request->session()->regenerate();
        $user->load('roles');

        return $this->redirectForUser($user);
    }

    /**
     * @param  array{label: string, email?: ?string, roles: list<string>}  $config
     */
    private function resolvePersonaUser(array $config): ?User
    {
        $email = trim((string) ($config['email'] ?? ''));
        if ($email !== '') {
            $byEmail = User::query()->where('email', $email)->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        foreach ($config['roles'] as $role) {
            $user = User::query()->role($role)->orderBy('id')->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    private function redirectForUser(User $user): RedirectResponse
    {
        if ($user->hasRole('admin') || $user->hasRole('Admin') || $user->hasRole('Super Admin') || $user->hasRole('Director')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('teacher') || $user->hasRole('Teacher') || $user->hasRole('Senior Teacher') || $user->hasRole('Academic Administrator')) {
            return redirect()->route('teacher.dashboard');
        }
        if ($user->hasRole('student') || $user->hasRole('Student')) {
            return redirect()->route('student.dashboard');
        }
        if ($user->hasRole('Accountant') || $user->hasRole('Finance Officer')) {
            if (\Illuminate\Support\Facades\Route::has('finance.dashboard')) {
                return redirect()->route('finance.dashboard');
            }

            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('home');
    }

    /**
     * @return list<array{key: string, label: string, color: string, available: bool}>
     */
    public static function availablePersonas(): array
    {
        if (! config('demo.quick_login_enabled')) {
            return [];
        }

        $out = [];
        $controller = new self;

        foreach (config('demo.personas', []) as $key => $config) {
            if (! is_array($config)) {
                continue;
            }
            $out[] = [
                'key' => $key,
                'label' => (string) ($config['label'] ?? $key),
                'color' => (string) ($config['color'] ?? '#334155'),
                'available' => $controller->resolvePersonaUser($config) !== null,
            ];
        }

        return $out;
    }
}
