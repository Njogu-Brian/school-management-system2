import { authApi } from '../../api/auth.api';
import { mapApiUser } from '../mapUser';
import { parseGoogleIdToken } from '../googleIdentity';
import type { AuthProviderResult, GoogleAuthInput, IAuthProvider } from './types';

/**
 * Exchanges a Google ID token for a Sanctum session via `POST /login/google`.
 * OAuth UI lives in the app layer (expo-auth-session); this class is the backend bridge.
 */
/** Strategy implementation — use the React `GoogleAuthProvider` for OAuth UI wiring. */
export class GoogleSignInStrategy implements IAuthProvider {
  readonly method = 'google' as const;

  async isAvailable(): Promise<boolean> {
    return true;
  }

  async authenticate(input: GoogleAuthInput): Promise<AuthProviderResult> {
    const idToken = input.idToken?.trim();
    if (!idToken) {
      throw new Error('Google sign-in did not return an ID token.');
    }

    const res = await authApi.loginWithGoogle({ id_token: idToken });
    if (!res.success || !res.data) {
      throw new Error(res.message || 'Google sign-in failed.');
    }

    const { token, user: rawUser, expires_at } = res.data;
    const user = mapApiUser(rawUser);
    const identity = parseGoogleIdToken(idToken);

    if (identity) {
      user.googleId = identity.sub;
      user.googleEmail = identity.email ?? user.email;
    }

    return {
      method: 'google',
      token,
      user,
      expiresAt: expires_at ?? null,
      rememberMe: true,
    };
  }
}
