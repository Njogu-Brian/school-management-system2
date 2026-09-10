import AsyncStorage from '@react-native-async-storage/async-storage';
import { ASYNC_KEYS } from './keys';

function keyForUser(userId: number | string): string {
  return `${ASYNC_KEYS.SELECTED_CHILD_ID}:${userId}`;
}

export async function getSelectedChildId(userId: number | string): Promise<number | null> {
  try {
    const raw = await AsyncStorage.getItem(keyForUser(userId));
    if (!raw) return null;
    const id = Number(raw);
    return Number.isFinite(id) && id > 0 ? id : null;
  } catch {
    return null;
  }
}

export async function setSelectedChildId(userId: number | string, studentId: number): Promise<void> {
  if (!Number.isFinite(studentId) || studentId <= 0) return;
  await AsyncStorage.setItem(keyForUser(userId), String(studentId));
}

export async function clearSelectedChildId(userId: number | string): Promise<void> {
  await AsyncStorage.removeItem(keyForUser(userId));
}
