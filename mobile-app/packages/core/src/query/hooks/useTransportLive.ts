import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { transportLiveApi } from '../../api/transportLive.api';
import {
  transportStopsApi,
  type MarkTransportStopPayload,
  type TransportStopKind,
} from '../../api/transportStops.api';

function normalizeFleet(data: unknown) {
  if (Array.isArray(data)) return data;
  if (data && typeof data === 'object' && Array.isArray((data as { runs?: unknown }).runs)) {
    return (data as { runs: unknown[] }).runs;
  }
  return [];
}

export function useLiveBusForStudent(studentId: number, options?: { enabled?: boolean; refetchInterval?: number }) {
  return useQuery({
    queryKey: ['transport-live', 'student', studentId] as const,
    enabled: (options?.enabled !== false) && studentId > 0,
    queryFn: async () => {
      const res = await transportLiveApi.forStudent(studentId);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load bus location.');
      const d = res.data;
      return {
        ...d,
        vehicle_registration: d.vehicle_registration ?? d.vehicle?.vehicle_number ?? null,
        age_seconds: d.age_seconds ?? d.freshness_seconds ?? null,
        freshness_seconds: d.freshness_seconds ?? d.age_seconds ?? null,
      };
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
      return normalizeFleet(res.data).map((bus) => {
        const b = bus as Record<string, unknown>;
        const freshness = (b.freshness_seconds ?? b.age_seconds) as number | null | undefined;
        return {
          ...b,
          vehicle_registration: (b.vehicle_registration ?? b.vehicle_number) as string | null | undefined,
          age_seconds: freshness,
          freshness_seconds: freshness,
        };
      });
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
      const d = res.data;
      const run = d.run;
      return {
        ...d,
        live: Boolean(run?.latitude != null && run?.longitude != null && run?.status === 'in_progress'),
        latitude: run?.latitude ?? d.latitude,
        longitude: run?.longitude ?? d.longitude,
        last_location_at: run?.last_location_at ?? d.last_location_at,
        freshness_seconds: run?.freshness_seconds ?? d.freshness_seconds,
        age_seconds: run?.freshness_seconds ?? d.age_seconds ?? d.freshness_seconds,
        vehicle_registration: d.vehicle_registration ?? d.vehicle?.vehicle_number ?? null,
        status: run?.status ?? d.status,
      };
    },
    refetchInterval: options?.refetchInterval ?? 5_000,
    staleTime: 2_000,
  });
}

export function useTripStops(
  tripId: number,
  options?: { enabled?: boolean; date?: string; kind?: TransportStopKind; refetchInterval?: number },
) {
  const date = options?.date ?? new Date().toISOString().slice(0, 10);
  return useQuery({
    queryKey: ['transport-stops', 'trip', tripId, date, options?.kind ?? ''] as const,
    enabled: (options?.enabled !== false) && tripId > 0,
    queryFn: async () => {
      const res = await transportStopsApi.forTrip(tripId, { date, kind: options?.kind });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load stops.');
      return res.data;
    },
    staleTime: 10_000,
    refetchInterval: options?.refetchInterval,
  });
}

export function useStudentStops(studentId: number, options?: { enabled?: boolean; kind?: TransportStopKind }) {
  return useQuery({
    queryKey: ['transport-stops', 'student', studentId, options?.kind ?? ''] as const,
    enabled: (options?.enabled !== false) && studentId > 0,
    queryFn: async () => {
      const res = await transportStopsApi.forStudent(studentId, { kind: options?.kind });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load child stops.');
      return res.data;
    },
    staleTime: 30_000,
  });
}

export function useMarkTransportStop(tripId: number, date?: string) {
  const qc = useQueryClient();
  const day = date ?? new Date().toISOString().slice(0, 10);

  return useMutation({
    mutationFn: async (payload: MarkTransportStopPayload) => {
      const res = await transportStopsApi.mark(tripId, { ...payload, date: payload.date ?? day });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to save stop.');
      return res.data;
    },
    onSuccess: (_data, variables) => {
      void qc.invalidateQueries({ queryKey: ['transport-stops', 'trip', tripId] });
      void qc.invalidateQueries({ queryKey: ['transport-stops', 'student', variables.student_id] });
      void qc.invalidateQueries({ queryKey: ['transport-live', 'student', variables.student_id] });
    },
  });
}
