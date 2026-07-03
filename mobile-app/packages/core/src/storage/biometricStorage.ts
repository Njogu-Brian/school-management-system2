import AsyncStorage from '@react-native-async-storage/async-storage';
import * as LocalAuthentication from 'expo-local-authentication';
import * as SecureStore from 'expo-secure-store';
import { ASYNC_KEYS, BIOMETRIC_SECURE_KEYS } from './keys';

const SECURE_OPTIONS: SecureStore.SecureStoreOptions = {
  keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
};

export const BIOMETRIC_MAX_FAILURES = 5;

type BiometricAuthBundle = {
  token: string;
};

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

export async function authenticateWithBiometrics(
  reason = 'Authenticate to unlock',
): Promise<boolean> {
  const result = await LocalAuthentication.authenticateAsync({
    promptMessage: reason,
    fallbackLabel: 'Use device passcode',
    disableDeviceFallback: false,
  });
  return result.success;
}

export async function setBiometricEnabled(enabled: boolean): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.BIOMETRIC_ENABLED, JSON.stringify(enabled));
  if (!enabled) {
    await SecureStore.deleteItemAsync(BIOMETRIC_SECURE_KEYS.AUTH_BUNDLE, SECURE_OPTIONS);
    await clearBiometricFailureCount();
  }
}

export async function getBiometricEnabled(): Promise<boolean> {
  const raw = await AsyncStorage.getItem(ASYNC_KEYS.BIOMETRIC_ENABLED);
  return raw ? (JSON.parse(raw) as boolean) : false;
}

/**
 * Store the current Sanctum token behind device biometrics. Does not replace
 * backend auth — only unlocks an existing session on this device.
 */
export async function saveBiometricAuthBundle(token: string): Promise<void> {
  const payload: BiometricAuthBundle = { token };
  await SecureStore.setItemAsync(
    BIOMETRIC_SECURE_KEYS.AUTH_BUNDLE,
    JSON.stringify(payload),
    { ...SECURE_OPTIONS, requireAuthentication: true },
  );
  await clearBiometricFailureCount();
}

export async function getBiometricAuthBundle(): Promise<BiometricAuthBundle | null> {
  try {
    const raw = await SecureStore.getItemAsync(BIOMETRIC_SECURE_KEYS.AUTH_BUNDLE, SECURE_OPTIONS);
    if (!raw) {
      return null;
    }
    return JSON.parse(raw) as BiometricAuthBundle;
  } catch {
    return null;
  }
}

export async function clearBiometricAuthBundle(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(BIOMETRIC_SECURE_KEYS.AUTH_BUNDLE, SECURE_OPTIONS);
  } catch {
    /* no-op */
  }
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

/** True when biometrics are enabled and a saved session bundle exists. */
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
  return Boolean(bundle?.token);
}
