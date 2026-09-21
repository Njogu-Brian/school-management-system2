import { authApi } from '../../api/auth.api';
import { errorMessage } from '../../utils/errors';
import {
  clearPinFailureCount,
  createPin,
  getRememberedUsername,
  hasPinUnlockAvailable,
  incrementPinFailureCount,
  isPinLoginLocked,
  PIN_MAX_FAILURES,
} from '../../storage/pinStorage';
import { mapApiUser } from '../mapUser';
import type { AuthProviderResult, IAuthProvider, PinAuthInput } from './types';

export class PinLoginLockedError extends Error {
  constructor() {
    super(
      `PIN sign-in is locked after ${PIN_MAX_FAILURES} failed attempts. Sign in with your password.`,
    );
    this.name = 'PinLoginLockedError';
  }
}

export class PinNoBundleError extends Error {
  constructor() {
    super('Enter your username, then your PIN — or sign in with your password once.');
    this.name = 'PinNoBundleError';
  }
}

/**
 * Account PIN — same digits on every device, like a password.
 * Local keypad cache is only for convenience after a successful unlock.
 */
export class PinUnlockStrategy implements IAuthProvider {
  readonly method = 'pin' as const;

  async isAvailable(): Promise<boolean> {
    return hasPinUnlockAvailable();
  }

  async authenticate(input: PinAuthInput): Promise<AuthProviderResult> {
    if (await isPinLoginLocked()) {
      throw new PinLoginLockedError();
    }

    const identifier =
      input.identifier?.trim() || (await getRememberedUsername())?.trim() || '';

    if (!identifier) {
      throw new PinNoBundleError();
    }

    try {
      const res = await authApi.loginWithPin({ identifier, pin: input.pin });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Incorrect PIN.');
      }
      await clearPinFailureCount();
      await createPin(input.pin, {
        token: res.data.token,
        userId: res.data.user.id,
        identifier,
      });
      return {
        method: 'pin',
        token: res.data.token,
        user: mapApiUser(res.data.user),
        expiresAt: res.data.expires_at ?? null,
        rememberMe: true,
      };
    } catch (err) {
      const failures = await incrementPinFailureCount();
      if (failures >= PIN_MAX_FAILURES) {
        throw new PinLoginLockedError();
      }
      throw new Error(errorMessage(err, 'Incorrect PIN.'));
    }
  }
}
