<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Multi-device session management for the Admin mobile app.
 */
class ApiSessionController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $currentId = $user->currentAccessToken()?->id;

        $sessions = $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PersonalAccessToken $token) => $this->formatToken($token, $currentId));

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    public function revoke(Request $request)
    {
        $request->validate([
            'token_id' => 'required_without:revoke_all|integer',
            'revoke_all' => 'nullable|boolean',
        ]);

        /** @var User $user */
        $user = $request->user();
        $current = $user->currentAccessToken();

        if ($request->boolean('revoke_all')) {
            $user->tokens()->where('id', '!=', $current?->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All other sessions revoked.',
                'data' => ['revoked' => 'others'],
            ]);
        }

        $token = $user->tokens()->where('id', $request->token_id)->firstOrFail();
        if ($current && (int) $token->id === (int) $current->id) {
            return response()->json([
                'success' => false,
                'message' => 'Use logout to end the current session.',
            ], 422);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session revoked.',
            'data' => ['revoked' => $request->token_id],
        ]);
    }

    public function refresh(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->load('roles', 'roles.permissions', 'staff');

        $current = $user->currentAccessToken();
        if ($current) {
            $current->delete();
        }

        $expiresAt = now()->addDays(7);
        $token = $user->createToken('mobile-app', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'expires_at' => $expiresAt->toIso8601String(),
            ],
        ]);
    }

    private function formatToken(PersonalAccessToken $token, ?int $currentId): array
    {
        return [
            'id' => $token->id,
            'name' => $token->name,
            'device' => $token->name,
            'platform' => 'mobile',
            'is_current' => $currentId !== null && (int) $token->id === (int) $currentId,
            'login_date' => $token->created_at?->toIso8601String(),
            'last_activity' => ($token->last_used_at ?? $token->created_at)?->toIso8601String(),
            'expires_at' => $token->expires_at?->toIso8601String(),
        ];
    }
}
