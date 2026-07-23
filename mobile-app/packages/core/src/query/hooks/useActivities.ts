import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { activitiesApi, type SaveActivityAttendancePayload } from '../../api/activities.api';

export function useActivities(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['activities', 'list'] as const,
    enabled: options?.enabled !== false,
    queryFn: async () => {
      const res = await activitiesApi.list();
      if (!res.success) throw new Error(res.message || 'Failed to load activities.');
      return res.data ?? [];
    },
    staleTime: 60_000,
  });
}

export function useActivityStudents(activityId: string | null, date?: string) {
  return useQuery({
    queryKey: ['activities', 'students', activityId ?? '', date ?? ''] as const,
    enabled: !!activityId,
    queryFn: async () => {
      const res = await activitiesApi.students(activityId as string, date);
      if (!res.success) throw new Error(res.message || 'Failed to load students.');
      return res.data ?? [];
    },
  });
}

export function useActivityAttendance(activityId: string | null, date: string) {
  return useQuery({
    queryKey: ['activities', 'attendance', activityId ?? '', date] as const,
    enabled: !!activityId,
    queryFn: async () => {
      const res = await activitiesApi.attendance(activityId as string, date);
      if (!res.success) throw new Error(res.message || 'Failed to load attendance.');
      return res.data ?? [];
    },
  });
}

export function useSaveActivityAttendance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: SaveActivityAttendancePayload) => {
      const res = await activitiesApi.saveAttendance(payload);
      if (!res.success) throw new Error(res.message || 'Failed to save activity attendance.');
      return res.data;
    },
    onSuccess: (_data, vars) => {
      void qc.invalidateQueries({ queryKey: ['activities', 'attendance', vars.activityId] });
    },
  });
}
