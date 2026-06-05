import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { teacherTransportApi } from '../../api/teacherTransport.api';
import { queryKeys } from '../queryKeys';

export function useTeacherTransportStudents(options?: { enabled?: boolean; date?: string }) {
  const date = options?.date ?? new Date().toISOString().slice(0, 10);
  return useQuery({
    queryKey: queryKeys.teacherTransport.students(date),
    queryFn: async () => {
      const res = await teacherTransportApi.getStudents({ date });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load transport roster.');
      return res.data;
    },
    enabled: options?.enabled !== false,
    staleTime: 30_000,
  });
}

export function useTeacherTransportActions() {
  const qc = useQueryClient();
  const invalidate = () => void qc.invalidateQueries({ queryKey: queryKeys.teacherTransport.all });
  const markPickup = useMutation({
    mutationFn: teacherTransportApi.markCollectedByParent,
    onSuccess: invalidate,
  });
  const cancelPickup = useMutation({
    mutationFn: teacherTransportApi.cancelPickup,
    onSuccess: invalidate,
  });
  return { markPickup, cancelPickup };
}
