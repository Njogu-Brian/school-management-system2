<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ParentForcedAction;
use App\Services\ParentCredentialsService;
use App\Support\PasswordPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $forced = (bool) ($user->must_change_password ?? false);

        return view('auth.passwords.change', [
            'forced' => $forced,
            'generated' => PasswordPolicy::generate(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $forced = (bool) ($user->must_change_password ?? false);

        $rules = [
            'new_password' => ['required', 'confirmed', PasswordPolicy::rule()],
        ];
        $rules['current_password'] = $forced ? ['nullable', 'string'] : ['required', 'string'];

        $validated = $request->validate($rules);

        $credentials = app(ParentCredentialsService::class);
        if (! $forced) {
            if (! $credentials->passwordIsValid($user, (string) $request->input('current_password'))) {
                return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
            }
        } elseif ($request->filled('current_password')) {
            if (! $credentials->passwordIsValid($user, (string) $request->input('current_password'))) {
                return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
            }
        }

        $newPassword = (string) $validated['new_password'];
        if (Hash::check($newPassword, (string) $user->password)) {
            return back()->withErrors(['new_password' => 'New password must be different from your current password.']);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        if ($user->parent_id) {
            ParentForcedAction::query()
                ->where('parent_info_id', $user->parent_id)
                ->where('type', ParentForcedAction::TYPE_CHANGE_PASSWORD)
                ->where('status', ParentForcedAction::STATUS_PENDING)
                ->get()
                ->each(fn ($a) => $a->markCompleted());
        }

        return redirect()->route('home')->with('success', 'Password updated successfully.');
    }
}
