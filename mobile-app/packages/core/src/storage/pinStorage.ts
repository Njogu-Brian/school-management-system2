import AsyncStorage from '@react-native-async-storage/async-storage';
import * as Crypto from 'expo-crypto';
import * as SecureStore from 'expo-secure-store';
import { ASYNC_KEYS, PIN_SECURE_KEYS } from './keys';

const SECURE_OPTIONS: SecureStore.SecureStoreOptions = {
  keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
};

export const PIN_MAX_FAILURES = 5;
export const PIN_MIN_LENGTH = 4;
export const PIN_MAX_LENGTH = 6;

export type PinAuthBundle = {
  token: string;
  userId?: number;
  identifier?: string;
  password?: string;
};

async function hashPin(pin: string, salt: string): Promise<string> {
  return Crypto.digestStringAsync(
    Crypto.CryptoDigestAlgorithm.SHA256,
    `${salt}:${pin}`,
  );
}

export async function getRememberedUsername(): Promise<string | null> {
  return AsyncStorage.getItem(ASYNC_KEYS.REMEMBERED_USERNAME);
}

export async function setRememberedUsername(username: string | null): Promise<void> {
  if (!username?.trim()) {
    await AsyncStorage.removeItem(ASYNC_KEYS.REMEMBERED_USERNAME);
    return;
  }
  await AsyncStorage.setItem(ASYNC_KEYS.REMEMBERED_USERNAME, username.trim());
}

export async function isPinEnabled(): Promise<boolean> {
  const raw = await AsyncStorage.getItem(ASYNC_KEYS.PIN_ENABLED);
  return raw ? (JSON.parse(raw) as boolean) : false;
}

async function setPinEnabledFlag(enabled: boolean): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.PIN_ENABLED, JSON.stringify(enabled));
}

export async function getPinFailureCount(): Promise<number> {
  const raw = await AsyncStorage.getItem(ASYNC_KEYS.PIN_FAILURE_COUNT);
  const n = raw ? parseInt(raw, 10) : 0;
  return Number.isFinite(n) ? n : 0;
}

export async function incrementPinFailureCount(): Promise<number> {
  const next = (await getPinFailureCount()) + 1;
  await AsyncStorage.setItem(ASYNC_KEYS.PIN_FAILURE_COUNT, String(next));
  return next;
}

export async function clearPinFailureCount(): Promise<void> {
  await AsyncStorage.removeItem(ASYNC_KEYS.PIN_FAILURE_COUNT);
}

export async function isPinLoginLocked(): Promise<boolean> {
  return (await getPinFailureCount()) >= PIN_MAX_FAILURES;
}

export async function clearPinEnrollment(): Promise<void> {
  await setPinEnabledFlag(false);
  await clearPinFailureCount();
  try {
    await SecureStore.deleteItemAsync(PIN_SECURE_KEYS.PIN_HASH, SECURE_OPTIONS);
    await SecureStore.deleteItemAsync(PIN_SECURE_KEYS.PIN_SALT, SECURE_OPTIONS);
    await SecureStore.deleteItemAsync(PIN_SECURE_KEYS.AUTH_BUNDLE, SECURE_OPTIONS);
  } catch {
    /* no-op */
  }
}

export async function createPin(
  pin: string,
  bundle: PinAuthBundle,
): Promise<void> {
  if (!/^\d+$/.test(pin) || pin.length < PIN_MIN_LENGTH || pin.length > PIN_MAX_LENGTH) {
    throw new Error(`PIN must be ${PIN_MIN_LENGTH}–${PIN_MAX_LENGTH} digits.`);
  }
  const salt = await Crypto.getRandomBytesAsync(16).then((bytes) =>
    Array.from(bytes)
      .map((b) => b.toString(16).padStart(2, '0'))
      .join(''),
  );
  const hash = await hashPin(pin, salt);
  await SecureStore.setItemAsync(PIN_SECURE_KEYS.PIN_SALT, salt, SECURE_OPTIONS);
  await SecureStore.setItemAsync(PIN_SECURE_KEYS.PIN_HASH, hash, SECURE_OPTIONS);
  await SecureStore.setItemAsync(
    PIN_SECURE_KEYS.AUTH_BUNDLE,
    JSON.stringify(bundle),
    SECURE_OPTIONS,
  );
  await setPinEnabledFlag(true);
  await clearPinFailureCount();
  if (bundle.identifier) {
    await setRememberedUsername(bundle.identifier);
  }
}

export async function verifyPin(pin: string): Promise<boolean> {
  const salt = await SecureStore.getItemAsync(PIN_SECURE_KEYS.PIN_SALT, SECURE_OPTIONS);
  const expected = await SecureStore.getItemAsync(PIN_SECURE_KEYS.PIN_HASH, SECURE_OPTIONS);
  if (!salt || !expected) {
    return false;
  }
  const actual = await hashPin(pin, salt);
  return actual === expected;
}

export async function getPinAuthBundle(): Promise<PinAuthBundle | null> {
  try {
    const raw = await SecureStore.getItemAsync(PIN_SECURE_KEYS.AUTH_BUNDLE, SECURE_OPTIONS);
    if (!raw) return null;
    return JSON.parse(raw) as PinAuthBundle;
  } catch {
    return null;
  }
}

export async function savePinAuthBundle(bundle: PinAuthBundle): Promise<void> {
  if (!(await isPinEnabled())) return;
  const existing = await getPinAuthBundle();
  const next: PinAuthBundle = {
    token: bundle.token,
    userId: bundle.userId ?? existing?.userId,
    identifier: bundle.identifier ?? existing?.identifier,
    password: bundle.password ?? existing?.password,
  };
  await SecureStore.setItemAsync(
    PIN_SECURE_KEYS.AUTH_BUNDLE,
    JSON.stringify(next),
    SECURE_OPTIONS,
  );
  if (next.identifier) {
    await setRememberedUsername(next.identifier);
  }
}

/** True when PIN unlock can be offered on the login screen. */
export async function hasPinUnlockAvailable(): Promise<boolean> {
  if (!(await isPinEnabled())) return false;
  if (await isPinLoginLocked()) return false;
  const bundle = await getPinAuthBundle();
  return Boolean(bundle?.token || (bundle?.identifier && bundle?.password));
}
