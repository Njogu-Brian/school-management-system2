import { useQuery } from '@tanstack/react-query';
import { communicationApi } from '../../api/communication.api';
import { queryKeys } from '../queryKeys';

export function useAnnouncements(options?: { enabled?: boolean; perPage?: number }) {
  return useQuery({
    queryKey: queryKeys.communication.announcements(),
    queryFn: async () => {
      const res = await communicationApi.listAnnouncements({
        per_page: options?.perPage ?? 30,
        status: 'published',
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load announcements.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 45_000,
  });
}
