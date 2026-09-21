import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Crypto from 'expo-crypto';
import * as LocalAuthentication from 'expo-local-authentication';
import { ASYNC_KEYS, BIOMETRIC_SECURE_KEYS } from './keys';
import { deleteKeychainItem, getKeychainItem, setKeychainItem } from './keychain';

export const BIOMETRIC_MAX_FAILURES = 5;

/**
 * Device-local biometric unlock. Face ID / fingerprint never leave this phone.
 * `selector` + `secret` are stored in this device's keychain and registered on the
 * server so a fresh session can be issued after logout or expiry — without PIN or password.
 */
export type BiometricAuthBundle = {
  selector: string;
  secret: string;
  userId?: number;
  identifier?: string;
};

function bytesToHex(bytes: Uint8Array): string {
  return Array.from(bytes)
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}

export async function generateBiometricUnlockPair(): Promise<{ selector: string; secret: string }> {
  const bytes = await Crypto.getRandomBytesAsync(32);
  const hex = bytesToHex(bytes);
  return {
    selector: hex.slice(0, 32),
    secret: hex,
  };
}

/** Device has biometric hardware and the user has enrolled biometrics. */
export async function canUseBiometrics(): Promise<boolean> {
  const hasHardware = await LocalAuthentication.hasHardwareAsync();
  const enrolled = await LocalAuthentication.isEnrolledAsync();
  return hasHardware && enrolled;
}

/** Generic label shown in UI (covers Face ID, fingerprint, iris, etc.). */
export async function getBiometricTypeLabel(): Promise<string> {
  return 'Biometrics';
}

export type BiometricPromptResult = 'success' | 'cancel' | 'failed';

export async function promptBiometrics(
  reason = 'Authenticate to unlock',
): Promise<BiometricPromptResult> {
  const result = await LocalAuthentication.authenticateAsync({
    promptMessage: reason,
    fallbackLabel: 'Use device passcode',
    disableDeviceFallback: false,
    cancelLabel: 'Cancel',
  });
  if (result.success) {
    return 'success';
  }
  const err = 'error' in result ? String(result.error) : '';
  if (
    err === 'user_cancel' ||
    err === 'system_cancel' ||
    err === 'app_cancel' ||
    err === 'user_fallback'
  ) {
    return 'cancel';
  }
  return 'failed';
}

export async function authenticateWithBiometrics(
  reason = 'Authenticate to unlock',
): Promise<boolean> {
  return (await promptBiometrics(reason)) === 'success';
}

export async function setBiometricEnabled(enabled: boolean): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.BIOMETRIC_ENABLED, JSON.stringify(enabled));
  if (!enabled) {
    await deleteKeychainItem(BIOMETRIC_SECURE_KEYS.AUTH_BUNDLE);
    await clearBiometricFailureCount();
  }
}

export async function getBiometricEnabled(): Promise<boolean> {
  const raw = await AsyncStorage.getItem(ASYNC_KEYS.BIOMETRIC_ENABLED);
  return raw ? (JSON.parse(raw) as boolean) : false;
}

export async function saveBiometricAuthBundle(bundle: BiometricAuthBundle): Promise<void> {
  const existing = await getBiometricAuthBundle();
  const payload: BiometricAuthBundle = {
    selector: bundle.selector,
    secret: bundle.secret,
    userId: bundle.userId ?? existing?.userId,
    identifier: bundle.identifier ?? existing?.identifier,
  };
  await setKeychainItem(BIOMETRIC_SECURE_KEYS.AUTH_BUNDLE, JSON.stringify(payload));
  await clearBiometricFailureCount();
}

export async function getBiometricAuthBundle(): Promise<BiometricAuthBundle | null> {
  try {
    const raw = await getKeychainItem(BIOMETRIC_SECURE_KEYS.AUTH_BUNDLE);
    if (!raw) {
      return null;
    }
    const parsed = JSON.parse(raw) as Partial<BiometricAuthBundle> & { token?: string };
    if (!parsed.selector || !parsed.secret) {
      return null;
    }
    return {
      selector: parsed.selector,
      secret: parsed.secret,
      userId: parsed.userId,
      identifier: parsed.identifier,
    };
  } catch {
    return null;
  }
}

export async function clearBiometricAuthBundle(): Promise<void> {
  await deleteKeychainItem(BIOMETRIC_SECURE_KEYS.AUTH_BUNDLE);
}

/** Disable biometrics and wipe the stored device unlock secret. */
export async function clearBiometricEnrollment(): Promise<void> {
  await setBiometricEnabled(false);
}

export async function getBiometricFailureCount(): Promise<number> {
  const raw = await AsyncStorage.getItem(ASYNC_KEYS.BIOMETRIC_FAILURE_COUNT);
  const n = raw ? parseInt(raw, 10) : 0;
  return Number.isFinite(n) ? n : 0;
}

export async function incrementBiometricFailureCount(): Promise<number> {
  const next = (await getBiometricFailureCount()) + 1;
  await AsyncStorage.setItem(ASYNC_KEYS.BIOMETRIC_FAILURE_COUNT, String(next));
  return next;
}

export async function clearBiometricFailureCount(): Promise<void> {
  await AsyncStorage.removeItem(ASYNC_KEYS.BIOMETRIC_FAILURE_COUNT);
}

export async function isBiometricLoginLocked(): Promise<boolean> {
  return (await getBiometricFailureCount()) >= BIOMETRIC_MAX_FAILURES;
}

/** True when this phone has enrolled Face ID / fingerprint unlock for the last account. */
export async function hasBiometricUnlockAvailable(): Promise<boolean> {
  if (!(await getBiometricEnabled())) {
    return false;
  }
  if (!(await canUseBiometrics())) {
    return false;
  }
  if (await isBiometricLoginLocked()) {
    return false;
  }
  const bundle = await getBiometricAuthBundle();
  return Boolean(bundle?.selector && bundle?.secret);
}
