import AsyncStorage from '@react-native-async-storage/async-storage';
import { ASYNC_KEYS } from './keys';

/**
 * App mode for dual-identity users (staff who are also parents, or admins with a
 * parent record). `work` = the staff/admin shell; `home` = the parent shell.
 */
export type AppMode = 'work' | 'home';

export async function getAppMode(): Promise<AppMode | null> {
  try {
    const raw = await AsyncStorage.getItem(ASYNC_KEYS.APP_MODE);
    if (raw === 'work' || raw === 'home') {
      return raw;
    }
  } catch {
    /* fall through to null */
  }
  return null;
}

export async function setAppMode(mode: AppMode): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.APP_MODE, mode);
}

export async function clearAppMode(): Promise<void> {
  await AsyncStorage.removeItem(ASYNC_KEYS.APP_MODE);
}
