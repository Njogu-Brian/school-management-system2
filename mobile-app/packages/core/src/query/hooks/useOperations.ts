import { useQuery } from '@tanstack/react-query';
import { operationsApi } from '../../api/operations.api';
import { queryKeys } from '../queryKeys';

export function useTransportRoute(routeId: number | null | undefined, options?: { enabled?: boolean }) {
  const id = routeId ?? 0;
  return useQuery({
    queryKey: queryKeys.operations.route(id),
    queryFn: async () => {
      const res = await operationsApi.getRoute(id);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load transport route.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && id > 0,
    staleTime: 60_000,
  });
}

export function useStudentRequirements(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.operations.studentRequirements(studentId),
    queryFn: async () => {
      const res = await operationsApi.getStudentRequirements(studentId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load student requirements.');
      }
      return res.data;
    },
    enabled: (options?.enabled !== false) && studentId > 0,
    staleTime: 45_000,
  });
}
