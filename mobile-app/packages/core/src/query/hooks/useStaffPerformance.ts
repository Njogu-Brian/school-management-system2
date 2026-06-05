import { useQuery } from '@tanstack/react-query';
import { staff360Api } from '../../api/staff360.api';
import { queryKeys } from '../queryKeys';

export function useStaffPerformanceReviews(staffId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staff.performanceReviews(staffId),
    queryFn: async () => {
      const res = await staff360Api.listPerformanceReviews(staffId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load performance reviews.');
      }
      return res.data.data ?? [];
    },
    enabled: (options?.enabled !== false) && staffId > 0,
    staleTime: 60_000,
  });
}

export function useStaffTrainingRecords(staffId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staff.trainingRecords(staffId),
    queryFn: async () => {
      const res = await staff360Api.listTrainingRecords(staffId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load training records.');
      }
      return res.data.data ?? [];
    },
    enabled: (options?.enabled !== false) && staffId > 0,
    staleTime: 60_000,
  });
}
