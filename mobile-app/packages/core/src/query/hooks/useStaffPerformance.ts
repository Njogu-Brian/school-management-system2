import { useQuery } from '@tanstack/react-query';
import { staff360Api } from '../../api/staff360.api';
import { queryKeys } from '../queryKeys';

export function useStaffPerformanceReviews(staffId: number, options?: { enabled?: boolean; perPage?: number }) {
  return useQuery({
    queryKey: queryKeys.staff.performanceReviews(staffId),
    queryFn: async () => {
      const res = await staff360Api.listPerformanceReviews(staffId, { per_page: options?.perPage ?? 50 });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load performance reviews.');
      }
      return res.data.data ?? [];
    },
    enabled: (options?.enabled !== false) && staffId > 0,
    staleTime: 60_000,
  });
}

export function useStaffPerformanceReview(
  staffId: number,
  reviewId: number,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.staff.performanceReview(staffId, reviewId),
    queryFn: async () => {
      const res = await staff360Api.getPerformanceReview(staffId, reviewId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load performance review.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && staffId > 0 && reviewId > 0,
    staleTime: 60_000,
  });
}

export function useStaffTrainingRecords(staffId: number, options?: { enabled?: boolean; perPage?: number }) {
  return useQuery({
    queryKey: queryKeys.staff.trainingRecords(staffId),
    queryFn: async () => {
      const res = await staff360Api.listTrainingRecords(staffId, { per_page: options?.perPage ?? 50 });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load training records.');
      }
      return res.data.data ?? [];
    },
    enabled: (options?.enabled !== false) && staffId > 0,
    staleTime: 60_000,
  });
}

export function useStaffTrainingRecord(
  staffId: number,
  recordId: number,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.staff.trainingRecord(staffId, recordId),
    queryFn: async () => {
      const res = await staff360Api.getTrainingRecord(staffId, recordId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load training record.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && staffId > 0 && recordId > 0,
    staleTime: 60_000,
  });
}
