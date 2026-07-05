import { useQueries, useQuery } from '@tanstack/react-query';
import { studentsApi } from '../../api/students.api';
import { buildAttendanceTrend, summarizeAttendanceDays } from '../../students/attendance';
import type { AttendanceCalendarDay } from '../../types/student360';
import { queryKeys } from '../queryKeys';

function monthOffsets(count: number): Array<{ year: number; month: number }> {
  const now = new Date();
  const result: Array<{ year: number; month: number }> = [];
  for (let i = 0; i < count; i++) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    result.push({ year: d.getFullYear(), month: d.getMonth() + 1 });
  }
  return result;
}

export function useStudentStats(studentId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.students.stats(studentId),
    queryFn: async () => {
      const res = await studentsApi.getStats(studentId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load student stats.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && studentId > 0,
    staleTime: 60_000,
  });
}

export function useStudentStatement(
  studentId: number,
  filters?: { year?: number; term_id?: number; academic_year_id?: number; detailed?: boolean },
  options?: { enabled?: boolean },
) {
  const detailed = filters?.detailed !== false;
  return useQuery({
    queryKey: [...queryKeys.students.all, 'statement', studentId, filters ?? {}],
    queryFn: async () => {
      const res = await studentsApi.getStatement(studentId, { ...filters, detailed });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load fee statement.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && studentId > 0,
    staleTime: 60_000,
  });
}

export function useStudentAttendanceCalendar(
  studentId: number,
  year: number,
  month: number,
  options?: { enabled?: boolean },
) {
  return useQuery({
    queryKey: queryKeys.students.attendanceCalendar(studentId, year, month),
    queryFn: async () => {
      const res = await studentsApi.getAttendanceCalendar(studentId, year, month);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load attendance.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && studentId > 0,
    staleTime: 45_000,
  });
}

/** Last 3 months of calendar data for trend + summary. */
export function useStudentAttendanceTrend(studentId: number, options?: { enabled?: boolean }) {
  const months = monthOffsets(3);

  const queries = useQueries({
    queries: months.map(({ year, month }) => ({
      queryKey: queryKeys.students.attendanceCalendar(studentId, year, month),
      queryFn: async (): Promise<AttendanceCalendarDay[]> => {
        const res = await studentsApi.getAttendanceCalendar(studentId, year, month);
        if (!res.success || !res.data) {
          throw new Error(res.message || 'Failed to load attendance.');
        }
        return res.data;
      },
      enabled: options?.enabled !== false && studentId > 0,
      staleTime: 45_000,
    })),
  });

  const isLoading = queries.some((q) => q.isLoading);
  const isError = queries.some((q) => q.isError);
  const monthBuckets = queries.map((q) => q.data ?? []);
  const allDays = monthBuckets.flat();
  const currentMonthDays = monthBuckets[0] ?? [];

  return {
    queries,
    isLoading,
    isError,
    allDays,
    currentMonthDays,
    summary: summarizeAttendanceDays(currentMonthDays),
    trend: buildAttendanceTrend(monthBuckets),
    refetch: () => queries.forEach((q) => void q.refetch()),
  };
}
