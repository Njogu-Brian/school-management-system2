import { authApi } from '../../api/auth.api';
import { sessionsApi } from '../../api/sessions.api';
import {
  authenticateWithBiometrics,
  clearBiometricFailureCount,
  getBiometricAuthBundle,
  incrementBiometricFailureCount,
  isBiometricLoginLocked,
  hasBiometricUnlockAvailable,
  saveBiometricAuthBundle,
  BIOMETRIC_MAX_FAILURES,
} from '../../storage/biometricStorage';
import { mapApiUser } from '../mapUser';
import { PasswordAuthProvider } from './PasswordAuthProvider';
import type { AuthProviderResult, BiometricAuthInput, IAuthProvider } from './types';

export class BiometricLoginLockedError extends Error {
  constructor() {
    super(
      `Biometric sign-in is locked after ${BIOMETRIC_MAX_FAILURES} failed attempts. Sign in with your password.`,
    );
    this.name = 'BiometricLoginLockedError';
  }
}

export class BiometricNoBundleError extends Error {
  constructor() {
    super('No saved session found. Sign in once with your email and password.');
    this.name = 'BiometricNoBundleError';
  }
}

const passwordProvider = new PasswordAuthProvider();

/**
 * Biometric unlock:
 * 1. Prefer stored credentials → fresh `POST /login` (works after logout).
 * 2. Fall back to saved Sanctum token + refresh.
 */
/** Strategy implementation — use the React `BiometricAuthProvider` for unlock UI state. */
export class BiometricUnlockStrategy implements IAuthProvider {
  readonly method = 'biometric' as const;

  async isAvailable(): Promise<boolean> {
    return hasBiometricUnlockAvailable();
  }

  async authenticate(_input: BiometricAuthInput): Promise<AuthProviderResult> {
    if (await isBiometricLoginLocked()) {
      throw new BiometricLoginLockedError();
    }

    const verified = await authenticateWithBiometrics('Authenticate to unlock Admin Console');
    if (!verified) {
      const failures = await incrementBiometricFailureCount();
      if (failures >= BIOMETRIC_MAX_FAILURES) {
        throw new BiometricLoginLockedError();
      }
      throw new Error('Biometric authentication was not successful.');
    }

    const bundle = await getBiometricAuthBundle();
    if (!bundle) {
      throw new BiometricNoBundleError();
    }

    if (bundle.identifier && bundle.password) {
      try {
        const result = await passwordProvider.authenticate({
          identifier: bundle.identifier,
          password: bundle.password,
          remember: true,
        });
        await clearBiometricFailureCount();
        await saveBiometricAuthBundle(result.token, {
          userId: result.user.id,
          identifier: bundle.identifier,
          password: bundle.password,
        });
        return { ...result, method: 'biometric' };
      } catch {
        /* fall through to token unlock */
      }
    }

    if (!bundle.token) {
      throw new BiometricNoBundleError();
    }

    const profile = await authApi.getProfileWithToken(bundle.token);
    if (!profile.success || !profile.data) {
      const failures = await incrementBiometricFailureCount();
      if (failures >= BIOMETRIC_MAX_FAILURES) {
        throw new BiometricLoginLockedError();
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
      /* keep existing token if refresh is unavailable */
    }

    await clearBiometricFailureCount();
    await saveBiometricAuthBundle(token, {
      userId: profile.data.id,
      identifier: bundle.identifier,
      password: bundle.password,
    });

    return {
      method: 'biometric',
      token,
      user: mapApiUser(profile.data),
      expiresAt,
      rememberMe: true,
    };
  }
}
