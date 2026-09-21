import { authApi } from '../../api/auth.api';
import { errorMessage } from '../../utils/errors';
import {
  promptBiometrics,
  clearBiometricFailureCount,
  getBiometricAuthBundle,
  incrementBiometricFailureCount,
  isBiometricLoginLocked,
  hasBiometricUnlockAvailable,
  BIOMETRIC_MAX_FAILURES,
} from '../../storage/biometricStorage';
import { mapApiUser } from '../mapUser';
import type { AuthProviderResult, BiometricAuthInput, IAuthProvider } from './types';

export class BiometricLoginLockedError extends Error {
  constructor() {
    super(
      `Biometric sign-in is locked after ${BIOMETRIC_MAX_FAILURES} failed attempts. Sign in with your password or PIN.`,
    );
    this.name = 'BiometricLoginLockedError';
  }
}

export class BiometricNoBundleError extends Error {
  constructor() {
    super('Biometrics are only set up on this phone. Sign in with your password or PIN, then enable biometrics here.');
    this.name = 'BiometricNoBundleError';
  }
}

export class BiometricCancelledError extends Error {
  constructor() {
    super('Biometric authentication was cancelled.');
    this.name = 'BiometricCancelledError';
  }
}

/**
 * This-device Face ID / fingerprint unlock.
 * After the OS confirms the person, a device secret (not the password) opens a new session.
 * That secret never copies to another phone.
 */
export class BiometricUnlockStrategy implements IAuthProvider {
  readonly method = 'biometric' as const;

  async isAvailable(): Promise<boolean> {
    return hasBiometricUnlockAvailable();
  }

  async authenticate(_input: BiometricAuthInput): Promise<AuthProviderResult> {
    if (await isBiometricLoginLocked()) {
      throw new BiometricLoginLockedError();
    }

    const prompt = await promptBiometrics('Unlock with biometrics');
    if (prompt === 'cancel') {
      throw new BiometricCancelledError();
    }
    if (prompt !== 'success') {
      const failures = await incrementBiometricFailureCount();
      if (failures >= BIOMETRIC_MAX_FAILURES) {
        throw new BiometricLoginLockedError();
      }
      throw new Error('Biometric authentication was not successful.');
    }

    const bundle = await getBiometricAuthBundle();
    if (!bundle?.selector || !bundle.secret) {
      throw new BiometricNoBundleError();
    }

    try {
      const res = await authApi.loginWithBiometric({
        selector: bundle.selector,
        secret: bundle.secret,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Biometric unlock failed.');
      }
      await clearBiometricFailureCount();
      return {
        method: 'biometric',
        token: res.data.token,
        user: mapApiUser(res.data.user),
        expiresAt: res.data.expires_at ?? null,
        rememberMe: true,
      };
    } catch (err) {
      const failures = await incrementBiometricFailureCount();
      if (failures >= BIOMETRIC_MAX_FAILURES) {
        throw new BiometricLoginLockedError();
      }
      throw new Error(errorMessage(err, 'Biometric unlock failed.'));
    }
  }
}
