import { useMutation, useQueryClient } from '@tanstack/react-query';
import {
  attendanceApi,
  type AttendanceMarkStatus,
  type MarkAttendancePayload,
} from '../../api/attendance.api';
import { queryKeys } from '../queryKeys';

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

export type { AttendanceMarkStatus };
