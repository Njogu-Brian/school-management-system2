import { ScreenRefreshProvider } from '@erp/ui';
import { useQueryClient } from '@tanstack/react-query';
import React, { useCallback, useState } from 'react';

/** Pull-to-refresh on every screen: refetch currently visible queries. */
export const ScreenRefreshBridge: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const queryClient = useQueryClient();
  const [refreshing, setRefreshing] = useState(false);

  const onRefresh = useCallback(async () => {
    setRefreshing(true);
    try {
      await queryClient.refetchQueries({ type: 'active' });
    } finally {
      setRefreshing(false);
    }
  }, [queryClient]);

  return (
    <ScreenRefreshProvider onRefresh={onRefresh} refreshing={refreshing}>
      {children}
    </ScreenRefreshProvider>
  );
};
