import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { notificationPreferencesApi, type NotificationPreferences } from '../../api/notificationPreferences.api';

const prefsKey = ['notification-preferences'] as const;

export function useNotificationPreferences(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: prefsKey,
    queryFn: async () => {
      const res = await notificationPreferencesApi.get();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load notification preferences.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useUpdateNotificationPreferences() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (prefs: NotificationPreferences) => {
      const res = await notificationPreferencesApi.update(prefs);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to save preferences.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: prefsKey });
    },
  });
}
