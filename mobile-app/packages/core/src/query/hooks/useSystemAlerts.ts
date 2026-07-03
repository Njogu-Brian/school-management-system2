import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { alertsApi } from '../../api/alerts.api';
import type { ApiError } from '../../types';

export const systemAlertsQueryKey = ['system-alerts'] as const;

export function useSystemAlerts(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: systemAlertsQueryKey,
    queryFn: async () => {
      try {
        const res = await alertsApi.list();
        if (!res.success || !res.data) {
          throw new Error(res.message || 'Failed to load system alerts.');
        }
        return res.data;
      } catch (err) {
        // Route not deployed yet on some servers — degrade gracefully.
        if ((err as ApiError)?.status === 404) {
          return { alerts: [], pending_count: 0 };
        }
        throw err;
      }
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
    retry: (failureCount, err) => (err as ApiError)?.status !== 404 && failureCount < 2,
  });
}

export function useAcknowledgeSystemAlert() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (id: string) => {
      const res = await alertsApi.acknowledge(id);
      if (!res.success) {
        throw new Error(res.message || 'Failed to acknowledge alert.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: systemAlertsQueryKey });
      void qc.invalidateQueries({ queryKey: ['notifications'] });
    },
  });
}
