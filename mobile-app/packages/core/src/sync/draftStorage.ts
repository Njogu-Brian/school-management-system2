import AsyncStorage from '@react-native-async-storage/async-storage';

const DRAFT_PREFIX = 'erp_draft_v1:';

export function attendanceDraftKey(date: string, classId: number, streamId: number | null): string {
  return `${DRAFT_PREFIX}attendance:${date}:${classId}:${streamId ?? 0}`;
}

export function marksDraftKey(examId: number, subjectId: number, classroomId: number): string {
  return `${DRAFT_PREFIX}marks:${examId}:${subjectId}:${classroomId}`;
}

export function marksMatrixDraftKey(examTypeId: number, classroomId: number, streamId?: number): string {
  return `${DRAFT_PREFIX}marks-matrix:${examTypeId}:${classroomId}:${streamId ?? 0}`;
}

export async function saveDraft<T>(key: string, data: T): Promise<void> {
  await AsyncStorage.setItem(key, JSON.stringify({ savedAt: new Date().toISOString(), data }));
}

export async function loadDraft<T>(key: string): Promise<T | null> {
  const raw = await AsyncStorage.getItem(key);
  if (!raw) return null;
  try {
    const parsed = JSON.parse(raw) as { data?: T };
    return parsed.data ?? null;
  } catch {
    return null;
  }
}

export async function clearDraft(key: string): Promise<void> {
  await AsyncStorage.removeItem(key);
}

export async function listDraftKeys(): Promise<string[]> {
  const keys = await AsyncStorage.getAllKeys();
  return keys.filter((k) => k.startsWith(DRAFT_PREFIX));
}
