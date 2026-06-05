import { useQuery } from '@tanstack/react-query';
import { approvalsApi } from '../../api/approvals.api';
import { payrollApi } from '../../api/payroll.api';
import { staffApi } from '../../api/staff.api';
import {
  summarizeStaffAttendance,
  toAttendanceDay,
  toLeaveBalanceItem,
  toPayrollSummary,
} from '../../staff/staff360';
import type { StaffAttendanceDay, StaffLeaveBalanceItem, StaffPayrollSummary } from '../../types/staff360';
import { queryKeys } from '../queryKeys';

function monthRange(): { startDate: string; endDate: string } {
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  const fmt = (d: Date) =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  return { startDate: fmt(start), endDate: fmt(end) };
}

export function useStaffLeaveBalances(staffId: number, options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: queryKeys.staff.leaveBalances(staffId),
    queryFn: async (): Promise<StaffLeaveBalanceItem[]> => {
      const res = await staffApi.leaveBalances(staffId);
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load leave balances.');
      }
      return res.data.balances.map(toLeaveBalanceItem);
    },
    enabled: options?.enabled !== false && staffId > 0,
    staleTime: 60_000,
  });
}

export function useStaffLeaveRequests(
  staffId: number,
  options?: { enabled?: boolean; status?: string; perPage?: number },
) {
  return useQuery({
    queryKey: queryKeys.staff.leaveRequests(staffId, options?.status),
    queryFn: async () => {
      const res = await approvalsApi.listLeaveRequests({
        staff_id: staffId,
        status: options?.status,
        per_page: options?.perPage ?? 50,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load leave history.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && staffId > 0,
    staleTime: 45_000,
  });
}

export function useStaffAttendanceHistory(
  staffId: number,
  options?: {
    enabled?: boolean;
    startDate?: string;
    endDate?: string;
    page?: number;
    perPage?: number;
  },
) {
  const range = monthRange();
  const startDate = options?.startDate ?? range.startDate;
  const endDate = options?.endDate ?? range.endDate;
  const page = options?.page ?? 1;

  const query = useQuery({
    queryKey: queryKeys.staff.attendanceHistory(staffId, { startDate, endDate, page }),
    queryFn: async () => {
      const res = await staffApi.attendanceHistory(staffId, {
        start_date: startDate,
        end_date: endDate,
        page,
        per_page: options?.perPage ?? 30,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load attendance history.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && staffId > 0,
    staleTime: 45_000,
  });

  const days: StaffAttendanceDay[] = (query.data?.history.data ?? [])
    .map(toAttendanceDay)
    .filter((d): d is StaffAttendanceDay => d != null);

  const summary = query.data?.summary
    ? summarizeStaffAttendance(query.data.summary)
    : {
        present: 0,
        absent: 0,
        late: 0,
        halfDay: 0,
        total: 0,
        percentage: null,
      };

  return {
    ...query,
    days,
    summary,
    range: { startDate, endDate },
    pagination: query.data?.history,
  };
}

export function useStaffPayrollRecords(
  staffId: number,
  options?: { enabled?: boolean; perPage?: number },
) {
  return useQuery({
    queryKey: queryKeys.staff.payrollRecords(staffId),
    queryFn: async () => {
      const res = await payrollApi.list({
        staff_id: staffId,
        per_page: options?.perPage ?? 12,
      });
      if (!res.success || !res.data) {
        throw new Error(res.message || 'Failed to load payroll records.');
      }
      return res.data;
    },
    enabled: options?.enabled !== false && staffId > 0,
    staleTime: 60_000,
  });
}

export function useStaffLatestPayroll(
  staffId: number,
  options?: { enabled?: boolean },
): { latest: StaffPayrollSummary | null; isLoading: boolean; isError: boolean } {
  const query = useStaffPayrollRecords(staffId, { enabled: options?.enabled, perPage: 1 });
  const first = query.data?.data?.[0];
  return {
    latest: first ? toPayrollSummary(first) : null,
    isLoading: query.isLoading,
    isError: query.isError,
  };
}
