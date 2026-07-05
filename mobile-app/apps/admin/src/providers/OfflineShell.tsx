import { getAppQueryClient, useNetworkStatus, useSyncQueue } from '@erp/core';
import { OfflineBanner } from '@erp/ui';
import React, { useEffect, useRef, useState } from 'react';
import { SyncConflictSheet } from './SyncConflictSheet';

/** Offline banner + sync queue drain — mount after login only. */
export const OfflineShell: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const networkStatus = useNetworkStatus();
  const prevStatus = useRef(networkStatus);
  const { pendingCount, conflictCount, refresh, process } = useSyncQueue();
  const [conflictsOpen, setConflictsOpen] = useState(false);

  useEffect(() => {
    if (prevStatus.current !== 'online' && networkStatus === 'online') {
      void getAppQueryClient().invalidateQueries();
      void process().then(({ processed, failed, conflicts }) => {
        if (processed > 0 || failed > 0 || conflicts > 0) {
          void getAppQueryClient().invalidateQueries({ queryKey: ['finance'] });
          void getAppQueryClient().invalidateQueries({ queryKey: ['academics'] });
          void getAppQueryClient().invalidateQueries({ queryKey: ['students'] });
          void getAppQueryClient().invalidateQueries({ queryKey: ['dashboard'] });
        }
        void refresh();
        if (conflicts > 0) setConflictsOpen(true);
      });
    }
    prevStatus.current = networkStatus;
  }, [networkStatus, process, refresh]);

  const onRetry = () => {
    if (conflictCount > 0) {
      setConflictsOpen(true);
      return;
    }
    void process().then(() => void refresh());
    void getAppQueryClient().refetchQueries({ type: 'active' });
  };

  return (
    <>
      <OfflineBanner
        status={networkStatus}
        pendingCount={pendingCount}
        conflictCount={conflictCount}
        onRetry={onRetry}
        onReviewConflicts={() => setConflictsOpen(true)}
      />
      <SyncConflictSheet visible={conflictsOpen} onClose={() => setConflictsOpen(false)} />
      {children}
    </>
  );
};
