import type { ApiError } from '../types';

/** Read a user-facing message from Error, ApiError, or a plain thrown object. */
export function errorMessage(err: unknown, fallback = 'Something went wrong.'): string {
  if (typeof err === 'string' && err.trim()) {
    return err.trim();
  }
  if (typeof err === 'object' && err && 'message' in err) {
    const msg = (err as ApiError).message;
    if (typeof msg === 'string' && msg.trim()) {
      return msg.trim();
    }
  }
  return fallback;
}
