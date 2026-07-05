import { getAppQueryClient, processSyncQueue, useNetworkStatus } from '@erp/core';
import { OfflineBanner } from '@erp/ui';
import React, { useEffect, useRef } from 'react';

/** Offline banner — only mount after login so NetInfo is not on the cold-start critical path. */
export const OfflineShell: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const networkStatus = useNetworkStatus();
  const prevStatus = useRef(networkStatus);

  useEffect(() => {
    if (prevStatus.current !== 'online' && networkStatus === 'online') {
      void getAppQueryClient().invalidateQueries();
      void processSyncQueue().then(({ processed, failed }) => {
        if (processed > 0 || failed > 0) {
          void getAppQueryClient().invalidateQueries({ queryKey: ['finance'] });
        }
      });
    }
    prevStatus.current = networkStatus;
  }, [networkStatus]);

  return (
    <>
      <OfflineBanner
        status={networkStatus}
        onRetry={() => void getAppQueryClient().refetchQueries({ type: 'active' })}
      />
      {children}
    </>
  );
};
