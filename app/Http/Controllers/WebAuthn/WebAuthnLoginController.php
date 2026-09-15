<?php

namespace App\Http\Controllers\WebAuthn;

use App\Models\User;
use App\Services\ParentCredentialsService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;

use function response;

class WebAuthnLoginController
{
    /**
     * Returns the challenge to assertion.
     */
    public function options(AssertionRequest $request): Responsable
    {
        return $request->toVerify($request->validate(['email' => 'sometimes|email|string']));
    }

    /**
     * Log the user in.
     */
    public function login(AssertedRequest $request): Response
    {
        if (! $request->login()) {
            return response()->noContent(422);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if ($user && $user->mustUseMobileApp()) {
            $username = $user->email ?: $user->phone_number;
            try {
                $resolved = app(ParentCredentialsService::class)->loginUsername($user);
                if (filled($resolved)) {
                    $username = $resolved;
                }
            } catch (\Throwable) {
                // keep fallback
            }

            Auth::logout();
            session()->flash('parent_use_app', true);
            session()->flash('parent_app_username', $username);
        }

        return response()->noContent(204);
    }
}
