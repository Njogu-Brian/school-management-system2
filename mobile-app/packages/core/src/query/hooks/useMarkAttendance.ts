import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  attendanceApi,
  type AttendanceMarkStatus,
  type AttendanceReasonFields,
  type AttendanceReportParams,
  type ConsecutiveAbsenceParams,
  type MarkAttendancePayload,
  type MarkStudentsPayload,
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
      void qc.invalidateQueries({ queryKey: queryKeys.attendance.all });
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
      void qc.invalidateQueries({ queryKey: queryKeys.attendance.all });
    },
  });
}

export function useAttendanceReport(
  params: AttendanceReportParams,
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.attendance.report(params),
    queryFn: async ({ pageParam }) => {
      const res = await attendanceApi.getReport({ ...params, page: pageParam as number, per_page: 30 });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load attendance report.');
      }
      return res.data;
    },
    initialPageParam: 1,
    getNextPageParam: (lastPage) =>
      lastPage.current_page < lastPage.last_page ? lastPage.current_page + 1 : undefined,
    enabled: options?.enabled !== false && Boolean(params.date),
    staleTime: 20_000,
  });
}

export function useConsecutiveAbsences(
  params: ConsecutiveAbsenceParams,
  options?: { enabled?: boolean },
) {
  return useInfiniteQuery({
    queryKey: queryKeys.attendance.consecutive(params),
    queryFn: async ({ pageParam }) => {
      const res = await attendanceApi.getConsecutive({
        ...params,
        page: pageParam as number,
        per_page: 30,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load consecutive absences.');
      }
      return res.data;
    },
    initialPageParam: 1,
    getNextPageParam: (lastPage) =>
      lastPage.current_page < lastPage.last_page ? lastPage.current_page + 1 : undefined,
    enabled: options?.enabled !== false,
    staleTime: 20_000,
  });
}

export function useMarkStudentsAttendance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: async (payload: MarkStudentsPayload) => {
      const res = await attendanceApi.markStudents(payload);
      if (!res.success) {
        throw new Error(res.message || 'Failed to save attendance.');
      }
      return res.data;
    },
    onSuccess: () => {
      void qc.invalidateQueries({ queryKey: queryKeys.dashboard.all });
      void qc.invalidateQueries({ queryKey: queryKeys.students.all });
      void qc.invalidateQueries({ queryKey: queryKeys.attendance.all });
    },
  });
}

export type { AttendanceMarkStatus, AttendanceReasonFields };
