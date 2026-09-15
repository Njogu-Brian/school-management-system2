import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  attendanceApi,
  type AttendanceMarkStatus,
  type AttendanceReasonFields,
  type MarkAttendancePayload,
} from '../../api/attendance.api';
import { queryKeys } from '../queryKeys';

export function useAttendanceReasonCodes(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.attendance.reasonCodes(),
    queryFn: async () => {
      const res = await attendanceApi.getReasonCodes();
      if (!res.success) {
        throw new Error(res.message || 'Failed to load attendance reasons.');
      }
      return res.data ?? [];
    },
    enabled: options?.enabled !== false,
    staleTime: 10 * 60_000,
  });
}

export function useMarkAttendance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: MarkAttendancePayload) => {
      const res = await attendanceApi.mark(payload);
      if (!res.success) {
        throw new Error(res.message || 'Failed to save attendance.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.dashboard.all });
      void qc.invalidateQueries({ queryKey: queryKeys.students.all });
    },
  });
}

export function useMarkStudentsAbsent() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: { date: string; student_ids: number[] } & AttendanceReasonFields) => {
      const res = await attendanceApi.markAbsent(payload);
      if (!res.success) {
        throw new Error(res.message || 'Failed to mark students absent.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.dashboard.all });
      void qc.invalidateQueries({ queryKey: queryKeys.students.all });
    },
  });
}

export type { AttendanceMarkStatus, AttendanceReasonFields };
