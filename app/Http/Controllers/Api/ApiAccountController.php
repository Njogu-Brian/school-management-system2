<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ParentCredentialsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ApiAccountController extends Controller
{
    public function changePassword(Request $request)
    {
        $user = $request->user();
        $forced = (bool) ($user->must_change_password ?? false);

        $rules = [
            'new_password' => [
                'required',
                'confirmed',
                \App\Support\PasswordPolicy::rule(),
            ],
        ];
        if (! $forced) {
            $rules['current_password'] = ['required', 'string'];
        } else {
            $rules['current_password'] = ['nullable', 'string'];
        }

        $request->validate($rules);

        $credentials = app(ParentCredentialsService::class);
        if (! $forced) {
            if (! $credentials->passwordIsValid($user, (string) $request->input('current_password'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your current password is incorrect.',
                ], 422);
            }
        } elseif ($request->filled('current_password')) {
            if (! $credentials->passwordIsValid($user, (string) $request->input('current_password'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your current password is incorrect.',
                ], 422);
            }
        }

        $newPassword = (string) $request->input('new_password');
        if (Hash::check($newPassword, (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from your current password.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        if ($user->parent_id) {
            \App\Models\ParentForcedAction::query()
                ->where('parent_info_id', $user->parent_id)
                ->where('type', \App\Models\ParentForcedAction::TYPE_CHANGE_PASSWORD)
                ->where('status', \App\Models\ParentForcedAction::STATUS_PENDING)
                ->get()
                ->each(fn ($a) => $a->markCompleted());
        }

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    public function setUnlockPin(Request $request)
    {
        $request->validate([
            'pin' => ['required', 'string', 'regex:/^\d{4,6}$/', 'confirmed'],
        ]);

        if (! Schema::hasColumn('users', 'unlock_pin_hash')) {
            return response()->json([
                'success' => false,
                'message' => 'PIN sign-in is not available yet. Please try again after the school updates the app.',
            ], 503);
        }

        $request->user()->forceFill([
            'unlock_pin_hash' => Hash::make((string) $request->input('pin')),
            'unlock_pin_set_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'PIN saved. You can use it to sign in on any of your devices.',
        ]);
    }

    public function clearUnlockPin(Request $request)
    {
        if (Schema::hasColumn('users', 'unlock_pin_hash')) {
            $request->user()->forceFill([
                'unlock_pin_hash' => null,
                'unlock_pin_set_at' => null,
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'PIN removed.',
        ]);
    }

    /**
     * Register this phone's biometric unlock secret. Face ID / fingerprint never leave the device;
     * the secret in the phone keychain is what signs the user back in after logout or expiry.
     */
    public function registerBiometricUnlock(Request $request)
    {
        $request->validate([
            'selector' => ['required', 'string', 'min:16', 'max:64'],
            'secret' => ['required', 'string', 'min:32', 'max:128'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! Schema::hasTable('user_biometric_unlocks')) {
            return response()->json([
                'success' => false,
                'message' => 'Biometric unlock is not available yet. Please try again after the school updates the app.',
            ], 503);
        }

        $selector = (string) $request->input('selector');
        $existing = \App\Models\UserBiometricUnlock::query()->where('selector', $selector)->first();
        if ($existing && (int) $existing->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Biometric unlock could not be registered on this device.',
            ], 409);
        }

        \App\Models\UserBiometricUnlock::query()->updateOrCreate(
            ['selector' => $selector],
            [
                'user_id' => $request->user()->id,
                'secret_hash' => Hash::make((string) $request->input('secret')),
                'device_name' => $request->input('device_name'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Biometric unlock is ready on this device.',
        ]);
    }

    public function revokeBiometricUnlock(Request $request)
    {
        if (! Schema::hasTable('user_biometric_unlocks')) {
            return response()->json(['success' => true, 'message' => 'Biometric unlock removed.']);
        }

        $selector = trim((string) $request->input('selector', ''));
        $query = \App\Models\UserBiometricUnlock::query()->where('user_id', $request->user()->id);
        if ($selector !== '') {
            $query->where('selector', $selector);
        }
        $query->delete();

        return response()->json([
            'success' => true,
            'message' => 'Biometric unlock removed.',
        ]);
    }
}
