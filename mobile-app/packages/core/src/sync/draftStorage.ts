import AsyncStorage from '@react-native-async-storage/async-storage';

const DRAFT_PREFIX = 'erp_draft_v1:';
const ATTENDANCE_DRAFT_PREFIX = `${DRAFT_PREFIX}attendance:`;

export function attendanceDraftKey(date: string, classId: number, streamId: number | null): string {
  return `${ATTENDANCE_DRAFT_PREFIX}${date}:${classId}:${streamId ?? 0}`;
}

/** Parse `erp_draft_v1:attendance:{date}:{classId}:{streamId}` keys stored on device. */
export function parseAttendanceDraftKey(
  key: string,
): { date: string; classId: number; streamId: number | null } | null {
  if (!key.startsWith(ATTENDANCE_DRAFT_PREFIX)) return null;
  const rest = key.slice(ATTENDANCE_DRAFT_PREFIX.length);
  const parts = rest.split(':');
  if (parts.length < 3) return null;
  const date = parts[0];
  const classId = Number(parts[1]);
  const streamRaw = Number(parts[2]);
  if (!date || !Number.isFinite(classId) || !Number.isFinite(streamRaw)) return null;
  return { date, classId, streamId: streamRaw === 0 ? null : streamRaw };
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
