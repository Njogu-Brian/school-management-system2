import { getAppQueryClient, shouldPersistQuery } from '@erp/core';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { createAsyncStoragePersister } from '@tanstack/query-async-storage-persister';
import { PersistQueryClientProvider } from '@tanstack/react-query-persist-client';
import React from 'react';

const persister = createAsyncStoragePersister({
  storage: AsyncStorage,
  key: 'erp_users_query_cache',
  throttleTime: 2000,
});

export const PersistedQueryProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const client = getAppQueryClient();

  return (
    <PersistQueryClientProvider
      client={client}
      persistOptions={{
        persister,
        maxAge: 24 * 60 * 60 * 1000,
        dehydrateOptions: {
          shouldDehydrateQuery: (query) =>
            query.state.status === 'success' && shouldPersistQuery(query.queryKey),
        },
      }}
      onSuccess={() => {
        void client.resumePausedMutations();
      }}
    >
      {children}
    </PersistQueryClientProvider>
  );
};
