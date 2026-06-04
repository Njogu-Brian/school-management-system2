import type { GoogleIdentity } from '../types';

/**
 * Decode the Google ID token payload (no signature verification — the backend
 * validates the token via Google's tokeninfo endpoint).
 */
export function parseGoogleIdToken(idToken: string): GoogleIdentity | null {
  try {
    const parts = idToken.split('.');
    if (parts.length < 2) {
      return null;
    }
    const payload = parts[1];
    const padded = payload.replace(/-/g, '+').replace(/_/g, '/');
    const json = atob(padded);
    const data = JSON.parse(json) as Record<string, unknown>;
    const sub = typeof data.sub === 'string' ? data.sub : null;
    if (!sub) {
      return null;
    }
    return {
      sub,
      email: typeof data.email === 'string' ? data.email : undefined,
      emailVerified: data.email_verified === true || data.email_verified === 'true',
      name: typeof data.name === 'string' ? data.name : undefined,
      picture: typeof data.picture === 'string' ? data.picture : undefined,
    };
  } catch {
    return null;
  }
}
