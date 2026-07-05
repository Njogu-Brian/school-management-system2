import AsyncStorage from '@react-native-async-storage/async-storage';
import type { SyncConflictRow } from './types';

const STORAGE_KEY = 'erp_sync_queue_v1';

export type SyncQueueItemStatus = 'pending' | 'processing' | 'failed' | 'conflict';

export interface SyncQueueItem {
  id: string;
  kind: string;
  label?: string;
  payload: Record<string, unknown>;
  createdAt: string;
  attempts: number;
  lastError?: string | null;
  status: SyncQueueItemStatus;
  conflicts?: SyncConflictRow[];
  forceApply?: boolean;
}

function newId(): string {
  return `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
}

async function readAll(): Promise<SyncQueueItem[]> {
  const raw = await AsyncStorage.getItem(STORAGE_KEY);
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw) as SyncQueueItem[];
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

async function writeAll(items: SyncQueueItem[]): Promise<void> {
  await AsyncStorage.setItem(STORAGE_KEY, JSON.stringify(items));
}

export async function enqueueSyncItem(
  kind: string,
  payload: Record<string, unknown>,
  options?: { label?: string },
): Promise<SyncQueueItem> {
  const item: SyncQueueItem = {
    id: newId(),
    kind,
    label: options?.label,
    payload,
    createdAt: new Date().toISOString(),
    attempts: 0,
    status: 'pending',
  };
  const items = await readAll();
  items.push(item);
  await writeAll(items);
  return item;
}

export async function listSyncQueue(): Promise<SyncQueueItem[]> {
  return readAll();
}

export async function getSyncItem(id: string): Promise<SyncQueueItem | null> {
  const items = await readAll();
  return items.find((i) => i.id === id) ?? null;
}

export async function removeSyncItem(id: string): Promise<void> {
  const items = await readAll();
  await writeAll(items.filter((i) => i.id !== id));
}

export async function markSyncItemFailed(id: string, error: string): Promise<void> {
  const items = await readAll();
  const next = items.map((i) =>
    i.id === id
      ? { ...i, status: 'failed' as const, attempts: i.attempts + 1, lastError: error }
      : i,
  );
  await writeAll(next);
}

export async function markSyncItemConflict(id: string, conflicts: SyncConflictRow[]): Promise<void> {
  const items = await readAll();
  const next = items.map((i) =>
    i.id === id ? { ...i, status: 'conflict' as const, conflicts, lastError: null } : i,
  );
  await writeAll(next);
}

export async function markSyncItemProcessing(id: string): Promise<void> {
  const items = await readAll();
  const next = items.map((i) =>
    i.id === id ? { ...i, status: 'processing' as const } : i,
  );
  await writeAll(next);
}

export async function resetSyncItem(id: string): Promise<void> {
  const items = await readAll();
  const next = items.map((i) =>
    i.id === id
      ? { ...i, status: 'pending' as const, lastError: null, conflicts: undefined, forceApply: false }
      : i,
  );
  await writeAll(next);
}

export async function forceApplySyncItem(id: string): Promise<void> {
  const items = await readAll();
  const next = items.map((i) =>
    i.id === id
      ? {
          ...i,
          status: 'pending' as const,
          forceApply: true,
          conflicts: undefined,
          lastError: null,
        }
      : i,
  );
  await writeAll(next);
}

export async function updateSyncItemPayload(
  id: string,
  payload: Record<string, unknown>,
): Promise<void> {
  const items = await readAll();
  const next = items.map((i) =>
    i.id === id ? { ...i, payload: { ...i.payload, ...payload }, status: 'pending' as const, conflicts: undefined } : i,
  );
  await writeAll(next);
}

export async function pendingSyncCount(): Promise<number> {
  const items = await readAll();
  return items.filter((i) => i.status === 'pending' || i.status === 'failed').length;
}

export async function conflictSyncCount(): Promise<number> {
  const items = await readAll();
  return items.filter((i) => i.status === 'conflict').length;
}

export async function syncQueueSummary(): Promise<{
  pending: number;
  failed: number;
  conflict: number;
  total: number;
}> {
  const items = await readAll();
  return {
    pending: items.filter((i) => i.status === 'pending').length,
    failed: items.filter((i) => i.status === 'failed').length,
    conflict: items.filter((i) => i.status === 'conflict').length,
    total: items.length,
  };
}
