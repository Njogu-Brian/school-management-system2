import AsyncStorage from '@react-native-async-storage/async-storage';
import { ASYNC_KEYS } from './keys';

export type ThemeMode = 'light' | 'dark' | 'auto';
export type SurfaceMode = 'default' | 'amoled';

export async function getThemeMode(): Promise<ThemeMode> {
  try {
    const raw = await AsyncStorage.getItem(ASYNC_KEYS.THEME_MODE);
    if (raw === 'light' || raw === 'dark' || raw === 'auto') {
      return raw;
    }
  } catch {
    /* defaults */
  }
  return 'auto';
}

export async function setThemeMode(mode: ThemeMode): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.THEME_MODE, mode);
}

export async function getSurfaceMode(): Promise<SurfaceMode> {
  try {
    const raw = await AsyncStorage.getItem(ASYNC_KEYS.SURFACE_MODE);
    if (raw === 'default' || raw === 'amoled') {
      return raw;
    }
  } catch {
    /* defaults */
  }
  return 'default';
}

export async function setSurfaceMode(mode: SurfaceMode): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.SURFACE_MODE, mode);
}
