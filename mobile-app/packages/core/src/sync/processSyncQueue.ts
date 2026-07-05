import { financeApi } from '../api/finance.api';
import {
  listSyncQueue,
  markSyncItemFailed,
  markSyncItemProcessing,
  removeSyncItem,
  resetSyncItem,
  type SyncQueueItem,
} from './syncQueue';

export type SyncProcessorResult = {
  processed: number;
  failed: number;
};

async function dispatchItem(item: SyncQueueItem): Promise<void> {
  switch (item.kind) {
    case 'finance.assign': {
      const id = Number(item.payload.transactionId);
      const type = item.payload.type as 'bank' | 'c2b';
      const studentId = Number(item.payload.studentId);
      const res = await financeApi.assignTransaction(id, type, studentId);
      if (!res.success) throw new Error(res.message || 'Assign failed.');
      return;
    }
    case 'finance.share': {
      const id = Number(item.payload.transactionId);
      const type = item.payload.type as 'bank' | 'c2b';
      const allocations = item.payload.allocations as Array<{ student_id: number; amount: number }>;
      const res = await financeApi.shareTransaction(id, type, allocations);
      if (!res.success) throw new Error(res.message || 'Share failed.');
      return;
    }
    default:
      throw new Error(`Unknown sync kind: ${item.kind}`);
  }
}

/** Drain pending queue items — call after NetInfo reconnect. */
export async function processSyncQueue(maxItems = 20): Promise<SyncProcessorResult> {
  const items = await listSyncQueue();
  const pending = items.filter((i) => i.status === 'pending' || i.status === 'failed');
  let processed = 0;
  let failed = 0;

  for (const item of pending.slice(0, maxItems)) {
    try {
      if (item.status === 'failed') {
        await resetSyncItem(item.id);
      }
      await markSyncItemProcessing(item.id);
      await dispatchItem(item);
      await removeSyncItem(item.id);
      processed += 1;
    } catch (err) {
      failed += 1;
      await markSyncItemFailed(item.id, (err as Error).message);
    }
  }

  return { processed, failed };
}
