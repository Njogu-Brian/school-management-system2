<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ParentCredentialsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
}
