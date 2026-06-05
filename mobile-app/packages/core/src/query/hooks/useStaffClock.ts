import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { staffClockApi } from '../../api/staffClock.api';
import { queryKeys } from '../queryKeys';

export function useStaffClockToday(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staffClock.today(),
    queryFn: async () => {
      const res = await staffClockApi.getTodayClockStatus();
      if (!res.success) throw new Error(res.message || 'Failed to load clock status.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 15_000,
  });
}

export function useStaffClockHistory(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staffClock.history(),
    queryFn: async () => {
      const res = await staffClockApi.getClockHistory(90);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load history.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useStaffGeofence(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staffClock.geofence(),
    queryFn: async () => {
      const res = await staffClockApi.getGeofenceConfig();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load geofence.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useStaffClockRoster(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staffClock.roster(),
    queryFn: async () => {
      const res = await staffClockApi.getClockRoster();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load staff roster.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 60_000,
  });
}

export function useStaffMemberClockHistory(staffId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staffClock.memberHistory(staffId),
    queryFn: async () => {
      const res = await staffClockApi.getStaffClockHistory(staffId, 90);
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load staff history.');
      return res.data;
    },
    enabled: options?.enabled !== false && staffId > 0,
    staleTime: 30_000,
  });
}

export function useStaffGeofenceUpdate() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: Parameters<typeof staffClockApi.updateGeofenceConfig>[0]) => {
      const res = await staffClockApi.updateGeofenceConfig(payload);
      if (!res.success) throw new Error(res.message || 'Failed to update geofence.');
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.staffClock.geofence() });
    },
  });
}

export function useStaffClockActions() {
  const qc = useQueryClient();
  const invalidate = () => {
    void qc.invalidateQueries({ queryKey: queryKeys.staffClock.all });
  };
  const clockIn = useMutation({
    mutationFn: async (payload: Parameters<typeof staffClockApi.clockIn>[0]) => {
      const res = await staffClockApi.clockIn(payload);
      if (!res.success) throw new Error(res.message || 'Clock-in failed.');
      return res;
    },
    onSuccess: invalidate,
  });
  const clockOut = useMutation({
    mutationFn: async (payload: Parameters<typeof staffClockApi.clockOut>[0]) => {
      const res = await staffClockApi.clockOut(payload);
      if (!res.success) throw new Error(res.message || 'Clock-out failed.');
      return res;
    },
    onSuccess: invalidate,
  });
  return { clockIn, clockOut };
}
