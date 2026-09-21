import * as SecureStore from 'expo-secure-store';

/**
 * Survives device lock after first unlock. `WHEN_UNLOCKED_THIS_DEVICE_ONLY`
 * made Face ID / fingerprint unlock fail once the phone had been locked.
 */
export const KEYCHAIN_WRITE: SecureStore.SecureStoreOptions = {
  keychainAccessible: SecureStore.AFTER_FIRST_UNLOCK_THIS_DEVICE_ONLY,
};

const READ_TRIES: Array<SecureStore.SecureStoreOptions | undefined> = [
  KEYCHAIN_WRITE,
  { keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY },
  { keychainAccessible: SecureStore.AFTER_FIRST_UNLOCK },
  undefined,
];

export async function getKeychainItem(key: string): Promise<string | null> {
  for (const opts of READ_TRIES) {
    try {
      const value = opts
        ? await SecureStore.getItemAsync(key, opts)
        : await SecureStore.getItemAsync(key);
      if (value) {
        return value;
      }
    } catch {
      /* try the next accessibility class */
    }
  }
  return null;
}

export async function setKeychainItem(key: string, value: string): Promise<void> {
  await SecureStore.setItemAsync(key, value, KEYCHAIN_WRITE);
}

export async function deleteKeychainItem(key: string): Promise<void> {
  for (const opts of READ_TRIES) {
    try {
      if (opts) {
        await SecureStore.deleteItemAsync(key, opts);
      } else {
        await SecureStore.deleteItemAsync(key);
      }
    } catch {
      /* ignore missing keys / accessibility mismatch */
    }
  }
}
