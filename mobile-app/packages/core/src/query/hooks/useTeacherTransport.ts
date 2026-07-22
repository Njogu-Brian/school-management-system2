import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { teacherTransportApi } from '../../api/teacherTransport.api';
import { queryKeys } from '../queryKeys';

export function useTeacherTransportStudents(options?: {
  enabled?: boolean;
  date?: string;
  classroom_id?: number | null;
  stream_id?: number | null;
  search?: string;
}) {
  const date = options?.date ?? new Date().toISOString().slice(0, 10);
  const classroomId = options?.classroom_id ?? null;
  const streamId = options?.stream_id ?? null;
  const search = options?.search?.trim() || undefined;
  return useQuery({
    queryKey: queryKeys.teacherTransport.students(date, classroomId, streamId, search),
    queryFn: async () => {
      const res = await teacherTransportApi.getStudents({
        date,
        classroom_id: classroomId ?? undefined,
        stream_id: streamId ?? undefined,
        search,
      });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load transport roster.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useTeacherTransportVehicles(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.teacherTransport.vehicles(),
    queryFn: async () => {
      const res = await teacherTransportApi.getVehiclesAndTrips();
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load vehicles.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 120_000,
  });
}

export function useTeacherTransportActions() {
  const qc = useQueryClient();
  const invalidate = () => void qc.invalidateQueries({ queryKey: queryKeys.teacherTransport.all });
  const markPickup = useMutation({
    mutationFn: async (payload: Parameters<typeof teacherTransportApi.markCollectedByParent>[0]) => {
      const res = await teacherTransportApi.markCollectedByParent(payload);
      if (!res.success) throw new Error(res.message || 'Failed to record pickup.');
      return res;
    },
    onSuccess: invalidate,
  });
  const cancelPickup = useMutation({
    mutationFn: async (pickupId: number) => {
      const res = await teacherTransportApi.cancelPickup(pickupId);
      if (!res.success) throw new Error(res.message || 'Failed to cancel pickup.');
      return res;
    },
    onSuccess: invalidate,
  });
  const reassign = useMutation({
    mutationFn: async (payload: Parameters<typeof teacherTransportApi.temporaryReassign>[0]) => {
      const res = await teacherTransportApi.temporaryReassign(payload);
      if (!res.success) throw new Error(res.message || 'Failed to reassign transport.');
      return res;
    },
    onSuccess: invalidate,
  });
  return { markPickup, cancelPickup, reassign };
}
