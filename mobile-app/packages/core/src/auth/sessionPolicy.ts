import type { PersistedSessionMeta } from '../types';

/** Absolute ceiling matching the Sanctum token lifetime (login issues a 7-day token). */
export const SESSION_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;

/** Idle window when "remember me" is off. */
export const SESSION_IDLE_MS = 30 * 60 * 1000;

/** Idle window when "remember me" is on (still capped by the absolute expiry). */
export const SESSION_IDLE_REMEMBER_MS = 7 * 24 * 60 * 60 * 1000;

/**
 * Decide whether a persisted session is expired. Three gates:
 *  1. server token expiry (`expiresAt` from login),
 *  2. absolute session age (defense in depth if the server omits expiry),
 *  3. idle timeout based on the remember-me preference.
 */
export function isSessionExpired(
  meta: PersistedSessionMeta | null,
  now: number = Date.now(),
): boolean {
  if (!meta) {
    return true;
  }
  const { expiresAt, startedAt, lastActivityAt, rememberMe } = meta;

  if (expiresAt != null && Number.isFinite(expiresAt) && now >= expiresAt) {
    return true;
  }
  if (Number.isFinite(startedAt) && now - startedAt > SESSION_MAX_AGE_MS) {
    return true;
  }
  const idleLimit = rememberMe ? SESSION_IDLE_REMEMBER_MS : SESSION_IDLE_MS;
  if (Number.isFinite(lastActivityAt) && now - lastActivityAt > idleLimit) {
    return true;
  }
  return false;
}

/** Parse an ISO8601 expiry into epoch ms (null when absent/invalid). */
export function parseExpiresAt(iso: string | null | undefined): number | null {
  if (!iso) {
    return null;
  }
  const ms = Date.parse(iso);
  return Number.isNaN(ms) ? null : ms;
}
