import AsyncStorage from '@react-native-async-storage/async-storage';
import type { PersistedSessionMeta, User } from '../types';
import { ASYNC_KEYS } from './keys';
import {
  clearRefreshToken,
  clearToken,
} from './secureStorage';

// --- Cached user -------------------------------------------------------------

export async function saveUser(user: User): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.USER, JSON.stringify(user));
}

export async function getCachedUser(): Promise<User | null> {
  try {
    const raw = await AsyncStorage.getItem(ASYNC_KEYS.USER);
    return raw ? (JSON.parse(raw) as User) : null;
  } catch {
    return null;
  }
}

export async function clearUser(): Promise<void> {
  await AsyncStorage.removeItem(ASYNC_KEYS.USER);
}

// --- Session metadata --------------------------------------------------------

export async function saveSessionMeta(meta: PersistedSessionMeta): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.SESSION_META, JSON.stringify(meta));
}

export async function getSessionMeta(): Promise<PersistedSessionMeta | null> {
  try {
    const raw = await AsyncStorage.getItem(ASYNC_KEYS.SESSION_META);
    return raw ? (JSON.parse(raw) as PersistedSessionMeta) : null;
  } catch {
    return null;
  }
}

export async function clearSessionMeta(): Promise<void> {
  await AsyncStorage.removeItem(ASYNC_KEYS.SESSION_META);
}

/** Update only the last-activity timestamp (idle-timeout tracking). */
export async function touchSessionMeta(): Promise<void> {
  const meta = await getSessionMeta();
  if (!meta) {
    return;
  }
  meta.lastActivityAt = Date.now();
  await saveSessionMeta(meta);
}

// --- Bulk clear --------------------------------------------------------------

/** Wipe every trace of the session (token, refresh, cached user, meta). */
export async function clearAuthData(): Promise<void> {
  await Promise.all([
    clearToken(),
    clearRefreshToken(),
    clearUser(),
    clearSessionMeta(),
  ]);
}
