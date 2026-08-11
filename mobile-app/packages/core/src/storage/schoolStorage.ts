import AsyncStorage from '@react-native-async-storage/async-storage';
import type { ResolvedSchool } from '../types';
import { ASYNC_KEYS } from './keys';

export async function saveSchoolContext(school: ResolvedSchool): Promise<void> {
  await AsyncStorage.setItem(ASYNC_KEYS.SCHOOL_CONTEXT, JSON.stringify(school));
}

export async function getSchoolContext(): Promise<ResolvedSchool | null> {
  try {
    const raw = await AsyncStorage.getItem(ASYNC_KEYS.SCHOOL_CONTEXT);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as ResolvedSchool;
    if (!parsed?.code || !parsed?.apiBaseUrl) return null;
    return parsed;
  } catch {
    return null;
  }
}

export async function clearSchoolContext(): Promise<void> {
  await AsyncStorage.removeItem(ASYNC_KEYS.SCHOOL_CONTEXT);
}
