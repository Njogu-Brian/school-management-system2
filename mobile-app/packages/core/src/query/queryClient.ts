import { QueryClient } from '@tanstack/react-query';

/**
 * Shared QueryClient defaults for Admin (and future Staff) apps.
 * Dashboard stats: short stale window; refetch on focus/reconnect for command center freshness.
 */
export function createAppQueryClient(): QueryClient {
  return new QueryClient({
    defaultOptions: {
      queries: {
        retry: 2,
        staleTime: 60_000,
        gcTime: 10 * 60_000,
        refetchOnWindowFocus: true,
        refetchOnReconnect: true,
      },
    },
  });
}

let defaultClient: QueryClient | null = null;

export function getAppQueryClient(): QueryClient {
  if (!defaultClient) {
    defaultClient = createAppQueryClient();
  }
  return defaultClient;
}
