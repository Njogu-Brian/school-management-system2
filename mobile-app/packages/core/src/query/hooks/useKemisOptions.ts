import { useQuery } from '@tanstack/react-query';
import { kemisApi } from '../../api/kemis.api';
import { queryKeys } from '../queryKeys';

export function useKemisOptions(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.kemis.options(),
    queryFn: async () => {
      const res = await kemisApi.options();
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load KEMIS options.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 3_600_000,
  });
}
