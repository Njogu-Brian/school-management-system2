<?php

namespace App\Support;

use App\Models\User;
use App\Services\ParentCredentialsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ParentWebPortalGate
{
    public static function reject(?User $user, ?string $typedIdentifier = null): RedirectResponse
    {
        $username = $typedIdentifier;
        if ($user) {
            try {
                $resolved = app(ParentCredentialsService::class)->loginUsername($user);
                if (filled($resolved)) {
                    $username = $resolved;
                }
            } catch (\Throwable) {
                // keep typed identifier
            }
        }

        if (Auth::check()) {
            Auth::logout();
        }

        return redirect()->route('login')->with([
            'parent_use_app' => true,
            'parent_app_username' => $username,
        ]);
    }
}
