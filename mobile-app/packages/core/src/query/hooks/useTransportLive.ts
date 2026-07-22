import { useQuery } from '@tanstack/react-query';
import { transportLiveApi } from '../../api/transportLive.api';

export function useLiveBusForStudent(studentId: number, options?: { enabled?: boolean; refetchInterval?: number }) {
  return useQuery({
    queryKey: ['transport-live', 'student', studentId] as const,
    enabled: (options?.enabled !== false) && studentId > 0,
    queryFn: async () => {
      const res = await transportLiveApi.forStudent(studentId);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load bus location.');
      return res.data;
    },
    refetchInterval: options?.refetchInterval ?? 5_000,
    staleTime: 2_000,
  });
}

export function useLiveFleet(options?: { enabled?: boolean; refetchInterval?: number }) {
  return useQuery({
    queryKey: ['transport-live', 'fleet'] as const,
    enabled: options?.enabled !== false,
    queryFn: async () => {
      const res = await transportLiveApi.fleet();
      if (!res.success) throw new Error(res.message || 'Failed to load live fleet.');
      return res.data ?? [];
    },
    refetchInterval: options?.refetchInterval ?? 5_000,
    staleTime: 2_000,
  });
}

export function useLiveTrip(tripId: number, options?: { date?: string; enabled?: boolean; refetchInterval?: number }) {
  return useQuery({
    queryKey: ['transport-live', 'trip', tripId, options?.date ?? ''] as const,
    enabled: (options?.enabled !== false) && tripId > 0,
    queryFn: async () => {
      const res = await transportLiveApi.forTrip(tripId, { date: options?.date });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load trip location.');
      return res.data;
    },
    refetchInterval: options?.refetchInterval ?? 5_000,
    staleTime: 2_000,
  });
}
