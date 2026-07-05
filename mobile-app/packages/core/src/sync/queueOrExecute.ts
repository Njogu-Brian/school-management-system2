import type { NetworkStatus } from '../hooks/useNetworkStatus';
import { enqueueSyncItem } from './syncQueue';
import type { SyncKind } from './types';
import { syncKindLabel } from './types';

export type QueueOrExecuteResult = 'synced' | 'queued';

/**
 * Execute immediately when online, otherwise enqueue for later sync.
 */
export async function queueOrExecute(
  kind: SyncKind | string,
  payload: Record<string, unknown>,
  execute: () => Promise<void>,
  networkStatus: NetworkStatus,
  options?: { label?: string },
): Promise<QueueOrExecuteResult> {
  if (networkStatus === 'offline') {
    await enqueueSyncItem(kind, payload, { label: options?.label ?? syncKindLabel(kind) });
    return 'queued';
  }
  await execute();
  return 'synced';
}
