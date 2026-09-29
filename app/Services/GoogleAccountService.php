<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Validate a Google ID token and resolve / link an existing school user.
 * Never creates users — email must already belong to a parent or staff account.
 */
class GoogleAccountService
{
    /**
     * @return array{ok: true, google_id: string, email: string, payload: array}|array{ok: false, message: string, status: int}
     */
    public function validateIdToken(string $idToken): array
    {
        $tokenInfo = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $tokenInfo->ok()) {
            return ['ok' => false, 'message' => 'Invalid Google token.', 'status' => 401];
        }

        $payload = $tokenInfo->json() ?? [];
        $aud = (string) ($payload['aud'] ?? '');
        $googleId = (string) ($payload['sub'] ?? '');
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $emailVerified = (string) ($payload['email_verified'] ?? 'false');

        $expectedAud = (string) config('services.google.client_id');
        if ($expectedAud !== '' && $aud !== $expectedAud) {
            // Accept Expo Android/iOS client IDs when listed as extra allowed audiences.
            $extra = array_filter(array_map('trim', explode(',', (string) env('GOOGLE_ALLOWED_CLIENT_IDS', ''))));
            if ($extra === [] || ! in_array($aud, $extra, true)) {
                return ['ok' => false, 'message' => 'Google token audience mismatch.', 'status' => 401];
            }
        }

        if ($googleId === '' || $email === '' || $emailVerified !== 'true') {
            return ['ok' => false, 'message' => 'Google account email must be verified.', 'status' => 401];
        }

        return [
            'ok' => true,
            'google_id' => $googleId,
            'email' => $email,
            'payload' => $payload,
        ];
    }

    /**
     * Find an existing user for Google sign-in / auto-link (no auto-create).
     */
    public function findUserForGoogleEmail(string $email): ?User
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
        if ($user) {
            return $user;
        }

        [$resolved] = app(LoginIdentifierService::class)->findUserAndStaff($email);

        return $resolved;
    }

    /**
     * Resolve user already linked by google_id, or find by email and link.
     *
     * @return array{ok: true, user: User}|array{ok: false, message: string, status: int}
     */
    public function resolveUserForLogin(string $googleId, string $email): array
    {
        $linked = User::where('google_id', $googleId)->first();
        if ($linked) {
            return ['ok' => true, 'user' => $linked];
        }

        $user = $this->findUserForGoogleEmail($email);
        if (! $user) {
            return [
                'ok' => false,
                'message' => 'No account found for this Google email. Please sign in with password/OTP first, then link Google in your profile.',
                'status' => 404,
            ];
        }

        $taken = User::where('google_id', $googleId)->where('id', '!=', $user->id)->exists();
        if ($taken) {
            return [
                'ok' => false,
                'message' => 'This Google account is already linked to another user.',
                'status' => 422,
            ];
        }

        $user->forceFill([
            'google_id' => $googleId,
            'google_email' => $email,
            'google_link_required' => false,
        ])->save();

        return ['ok' => true, 'user' => $user->fresh()];
    }

    /**
     * Link Google to the currently authenticated user only.
     *
     * @return array{ok: true, user: User}|array{ok: false, message: string, status: int}
     */
    public function linkToUser(User $user, string $googleId, string $email): array
    {
        $other = User::where('google_id', $googleId)->where('id', '!=', $user->id)->first();
        if ($other) {
            return [
                'ok' => false,
                'message' => 'This Google account is already linked to another user.',
                'status' => 422,
            ];
        }

        // Prefer matching an identifier already on this account.
        $match = $this->findUserForGoogleEmail($email);
        if ($match && (int) $match->id !== (int) $user->id) {
            return [
                'ok' => false,
                'message' => 'This Google email belongs to a different school account.',
                'status' => 422,
            ];
        }

        $user->forceFill([
            'google_id' => $googleId,
            'google_email' => $email,
            'google_link_required' => false,
        ])->save();

        return ['ok' => true, 'user' => $user->fresh()];
    }

    public function unlink(User $user): void
    {
        $user->forceFill([
            'google_id' => null,
            'google_email' => null,
        ])->save();
    }
}
