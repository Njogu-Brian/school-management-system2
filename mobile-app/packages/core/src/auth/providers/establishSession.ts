import type { SessionContextValue } from '../SessionContext';
import type { AuthProviderResult } from './types';
import { saveUser } from '../../storage/authStorage';

/**
 * Shared post-auth hook: persist token + user after any provider succeeds.
 * Biometrics never skip this — they only rehydrate an existing backend session.
 */
export async function establishSessionFromResult(
  session: Pick<SessionContextValue, 'setSession'>,
  result: AuthProviderResult,
): Promise<void> {
  await session.setSession({
    token: result.token,
    expiresAt: result.expiresAt,
    rememberMe: result.rememberMe ?? true,
  });
  await saveUser(result.user);
}
