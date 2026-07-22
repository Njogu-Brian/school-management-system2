import { authApi } from '../../api/auth.api';
import { sessionsApi } from '../../api/sessions.api';
import {
  clearPinFailureCount,
  getPinAuthBundle,
  hasPinUnlockAvailable,
  incrementPinFailureCount,
  isPinLoginLocked,
  PIN_MAX_FAILURES,
  savePinAuthBundle,
  verifyPin,
} from '../../storage/pinStorage';
import { mapApiUser } from '../mapUser';
import { PasswordAuthProvider } from './PasswordAuthProvider';
import type { AuthProviderResult, IAuthProvider } from './types';

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
    super('No saved session for PIN unlock. Sign in once with your email and password.');
    this.name = 'PinNoBundleError';
  }
}

const passwordProvider = new PasswordAuthProvider();

export type PinAuthInput = { pin: string };

/** PIN unlock — verifies local PIN then reuses stored credentials/token. */
export class PinUnlockStrategy implements IAuthProvider {
  readonly method = 'pin' as const;

  async isAvailable(): Promise<boolean> {
    return hasPinUnlockAvailable();
  }

  async authenticate(input: PinAuthInput): Promise<AuthProviderResult> {
    if (await isPinLoginLocked()) {
      throw new PinLoginLockedError();
    }

    const ok = await verifyPin(input.pin);
    if (!ok) {
      const failures = await incrementPinFailureCount();
      if (failures >= PIN_MAX_FAILURES) {
        throw new PinLoginLockedError();
      }
      throw new Error('Incorrect PIN.');
    }

    const bundle = await getPinAuthBundle();
    if (!bundle) {
      throw new PinNoBundleError();
    }

    if (bundle.identifier && bundle.password) {
      try {
        const result = await passwordProvider.authenticate({
          identifier: bundle.identifier,
          password: bundle.password,
          remember: true,
        });
        await clearPinFailureCount();
        await savePinAuthBundle({
          token: result.token,
          userId: result.user.id,
          identifier: bundle.identifier,
          password: bundle.password,
        });
        return { ...result, method: 'pin' };
      } catch {
        /* fall through to token unlock */
      }
    }

    if (!bundle.token) {
      throw new PinNoBundleError();
    }

    const profile = await authApi.getProfileWithToken(bundle.token);
    if (!profile.success || !profile.data) {
      const failures = await incrementPinFailureCount();
      if (failures >= PIN_MAX_FAILURES) {
        throw new PinLoginLockedError();
      }
      throw new Error(profile.message || 'Session expired. Sign in again.');
    }

    let token = bundle.token;
    let expiresAt: string | null = null;
    try {
      const refreshed = await sessionsApi.refreshWithToken(bundle.token);
      if (refreshed.success && refreshed.data?.token) {
        token = refreshed.data.token;
        expiresAt = refreshed.data.expires_at ?? null;
      }
    } catch {
      /* keep existing token */
    }

    await clearPinFailureCount();
    await savePinAuthBundle({
      token,
      userId: profile.data.id,
      identifier: bundle.identifier,
      password: bundle.password,
    });

    return {
      method: 'pin',
      token,
      user: mapApiUser(profile.data),
      expiresAt,
      rememberMe: true,
    };
  }
}
