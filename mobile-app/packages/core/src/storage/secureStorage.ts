import { deleteKeychainItem, getKeychainItem, setKeychainItem } from './keychain';
import { SECURE_KEYS } from './keys';

// --- Access token ------------------------------------------------------------

export async function saveToken(token: string): Promise<void> {
  await setKeychainItem(SECURE_KEYS.TOKEN, token);
}

export async function getToken(): Promise<string | null> {
  return getKeychainItem(SECURE_KEYS.TOKEN);
}

export async function clearToken(): Promise<void> {
  await deleteKeychainItem(SECURE_KEYS.TOKEN);
}

// --- Refresh token (forward-compatible; no backend endpoint yet) -------------

export async function saveRefreshToken(token: string): Promise<void> {
  await setKeychainItem(SECURE_KEYS.REFRESH_TOKEN, token);
}

export async function getRefreshToken(): Promise<string | null> {
  return getKeychainItem(SECURE_KEYS.REFRESH_TOKEN);
}

export async function clearRefreshToken(): Promise<void> {
  await deleteKeychainItem(SECURE_KEYS.REFRESH_TOKEN);
}
