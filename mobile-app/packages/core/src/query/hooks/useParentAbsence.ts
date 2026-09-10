import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { parentAbsenceApi } from '../../api/parentAbsence.api';
import { queryKeys } from '../queryKeys';

export function useAttendanceReasonCodes(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['attendance', 'reason-codes'],
    queryFn: async () => {
      const res = await parentAbsenceApi.listReasonCodes();
      if (!res.success) throw new Error(res.message || 'Failed to load reason codes');
      return res.data ?? [];
    },
    enabled: options?.enabled ?? true,
  });
}

export function useParentAbsenceHistory(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: ['attendance', 'absence-history', studentId],
    queryFn: async () => {
      const res = await parentAbsenceApi.history(studentId);
      if (!res.success) throw new Error(res.message || 'Failed to load absence history');
      return res.data ?? [];
    },
    enabled: (options?.enabled ?? true) && studentId > 0,
  });
}

export function useReportParentAbsence(studentId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: {
      start_date: string;
      end_date: string;
      reason: string;
      reason_code_id?: number | null;
    }) => {
      const res = await parentAbsenceApi.report(studentId, payload);
      if (!res.success || !res.data) throw new Error(res.message || 'Could not submit absence');
      return res;
    },
    onSuccess: async () => {
      await Promise.all([
        qc.invalidateQueries({ queryKey: ['attendance', 'absence-history', studentId] }),
        qc.invalidateQueries({ queryKey: [...queryKeys.students.all, 'attendance', studentId] }),
        qc.invalidateQueries({ queryKey: queryKeys.students.attendanceTrend(studentId) }),
      ]);
    },
  });
}
