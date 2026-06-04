import type { ApprovalListFilters } from '../types/approval';
import type { DashboardStatsFilters } from '../types/dashboard';
import type { StudentListFilters } from '../types/student';

/** Centralized TanStack Query keys for cache identity and invalidation. */
export const queryKeys = {
  dashboard: {
    all: ['dashboard'] as const,
    stats: (filters?: DashboardStatsFilters) =>
      [...queryKeys.dashboard.all, 'stats', filters ?? {}] as const,
    pendingApprovals: () => [...queryKeys.dashboard.all, 'pending-approvals'] as const,
  },
  approvals: {
    all: ['approvals'] as const,
    list: (filters?: ApprovalListFilters) =>
      [...queryKeys.approvals.all, 'list', filters ?? {}] as const,
    detail: (id: string) => [...queryKeys.approvals.all, 'detail', id] as const,
  },
  students: {
    all: ['students'] as const,
    list: (filters?: StudentListFilters) =>
      [...queryKeys.students.all, 'list', filters ?? {}] as const,
    detail: (id: number) => [...queryKeys.students.all, 'detail', id] as const,
    classrooms: () => [...queryKeys.students.all, 'classrooms'] as const,
    streams: (classId: number) => [...queryKeys.students.all, 'streams', classId] as const,
    stats: (id: number) => [...queryKeys.students.all, 'stats', id] as const,
    attendanceCalendar: (id: number, year: number, month: number) =>
      [...queryKeys.students.all, 'attendance', id, year, month] as const,
    attendanceTrend: (id: number) => [...queryKeys.students.all, 'attendance-trend', id] as const,
    statement: (id: number, year: number) =>
      [...queryKeys.students.all, 'statement', id, year] as const,
  },
};
