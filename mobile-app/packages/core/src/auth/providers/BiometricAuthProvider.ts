import { authApi } from '../../api/auth.api';
import {
  authenticateWithBiometrics,
  getBiometricAuthBundle,
  incrementBiometricFailureCount,
  isBiometricLoginLocked,
  clearBiometricFailureCount,
  hasBiometricUnlockAvailable,
  BIOMETRIC_MAX_FAILURES,
} from '../../storage/biometricStorage';
import { mapApiUser } from '../mapUser';
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
    super('No saved session found. Sign in once with your password or Google.');
    this.name = 'BiometricNoBundleError';
  }
}

/**
 * Unlocks an existing Sanctum session stored behind device biometrics.
 * Never calls `/login` — only rehydrates the token and validates with `GET /user`.
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
    if (!bundle?.token) {
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

    await clearBiometricFailureCount();

    return {
      method: 'biometric',
      token: bundle.token,
      user: mapApiUser(profile.data),
      expiresAt: null,
      rememberMe: true,
    };
  }
}
