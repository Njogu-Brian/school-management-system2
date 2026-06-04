import * as SecureStore from 'expo-secure-store';
import { SECURE_KEYS } from './keys';

const SECURE_OPTIONS: SecureStore.SecureStoreOptions = {
  keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY,
};

// --- Access token ------------------------------------------------------------

export async function saveToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(SECURE_KEYS.TOKEN, token, SECURE_OPTIONS);
}

export async function getToken(): Promise<string | null> {
  try {
    return await SecureStore.getItemAsync(SECURE_KEYS.TOKEN, SECURE_OPTIONS);
  } catch {
    return null;
  }
}

export async function clearToken(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(SECURE_KEYS.TOKEN, SECURE_OPTIONS);
  } catch {
    /* no-op: deleting a missing key is fine */
  }
}

// --- Refresh token (forward-compatible; no backend endpoint yet) -------------

export async function saveRefreshToken(token: string): Promise<void> {
  await SecureStore.setItemAsync(SECURE_KEYS.REFRESH_TOKEN, token, SECURE_OPTIONS);
}

export async function getRefreshToken(): Promise<string | null> {
  try {
    return await SecureStore.getItemAsync(SECURE_KEYS.REFRESH_TOKEN, SECURE_OPTIONS);
  } catch {
    return null;
  }
}

export async function clearRefreshToken(): Promise<void> {
  try {
    await SecureStore.deleteItemAsync(SECURE_KEYS.REFRESH_TOKEN, SECURE_OPTIONS);
  } catch {
    /* no-op */
  }
}
