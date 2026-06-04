import { authApi } from '../../api/auth.api';
import type { ApiError } from '../../types';
import { mapApiUser } from '../mapUser';
import type { AuthProviderResult, IAuthProvider, PasswordAuthInput } from './types';

/** Email/phone + password via `POST /login`. */
export class PasswordAuthProvider implements IAuthProvider {
  readonly method = 'password' as const;

  async isAvailable(): Promise<boolean> {
    return true;
  }

  async authenticate(input: PasswordAuthInput): Promise<AuthProviderResult> {
    const res = await authApi.login(input);
    if (!res.success || !res.data) {
      throw new Error(res.message || 'Login failed.');
    }
    const { token, user: rawUser, expires_at } = res.data;
    return {
      method: 'password',
      token,
      user: mapApiUser(rawUser),
      expiresAt: expires_at ?? null,
      rememberMe: input.remember ?? false,
    };
  }
}

export function toAuthError(err: unknown): string {
  return (
    (err as ApiError)?.message ||
    (err instanceof Error ? err.message : null) ||
    'Authentication failed. Please try again.'
  );
}
