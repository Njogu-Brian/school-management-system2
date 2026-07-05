import { useCallback, useEffect, useState } from 'react';
import {
  conflictSyncCount,
  forceApplySyncItem,
  listSyncQueue,
  pendingSyncCount,
  removeSyncItem,
  syncQueueSummary,
  type SyncQueueItem,
} from '../sync/syncQueue';
import { processSyncQueue, retryConflictItems } from '../sync/processSyncQueue';

export function useSyncQueue() {
  const [items, setItems] = useState<SyncQueueItem[]>([]);
  const [summary, setSummary] = useState({ pending: 0, failed: 0, conflict: 0, total: 0 });
  const [loading, setLoading] = useState(false);

  const refresh = useCallback(async () => {
    const [queue, stats] = await Promise.all([listSyncQueue(), syncQueueSummary()]);
    setItems(queue);
    setSummary(stats);
  }, []);

  useEffect(() => {
    void refresh();
    const interval = setInterval(() => void refresh(), 5000);
    return () => clearInterval(interval);
  }, [refresh]);

  const process = useCallback(async () => {
    setLoading(true);
    try {
      const result = await processSyncQueue();
      await refresh();
      return result;
    } finally {
      setLoading(false);
    }
  }, [refresh]);

  const discardItem = useCallback(
    async (id: string) => {
      await removeSyncItem(id);
      await refresh();
    },
    [refresh],
  );

  const forceLocal = useCallback(
    async (id: string) => {
      await forceApplySyncItem(id);
      await process();
    },
    [process],
  );

  const retryConflicts = useCallback(async () => {
    setLoading(true);
    try {
      await retryConflictItems();
      await refresh();
    } finally {
      setLoading(false);
    }
  }, [refresh]);

  return {
    items,
    summary,
    loading,
    refresh,
    process,
    discardItem,
    forceLocal,
    retryConflicts,
    pendingCount: summary.pending + summary.failed,
    conflictCount: summary.conflict,
  };
}

export async function getSyncQueueCounts(): Promise<{ pending: number; conflict: number }> {
  const [pending, conflict] = await Promise.all([pendingSyncCount(), conflictSyncCount()]);
  return { pending, conflict };
}
