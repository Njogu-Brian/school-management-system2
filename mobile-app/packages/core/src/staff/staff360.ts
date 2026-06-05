import type {
  PayrollRecordRow,
  StaffAttendanceDay,
  StaffAttendanceHistoryRow,
  StaffAttendanceSummary,
  StaffLeaveBalanceItem,
  StaffLeaveBalanceRecord,
  StaffPayrollSummary,
} from '../types/staff360';

export function toLeaveBalanceItem(raw: StaffLeaveBalanceRecord): StaffLeaveBalanceItem {
  return {
    id: raw.id,
    leaveTypeId: raw.leave_type_id,
    leaveTypeName: raw.leave_type_name ?? 'Leave',
    leaveTypeCode: raw.leave_type_code ?? null,
    entitlementDays: raw.entitlement_days,
    usedDays: raw.used_days,
    remainingDays: raw.remaining_days,
    carriedForward: raw.carried_forward,
  };
}

export function toAttendanceDay(raw: StaffAttendanceHistoryRow): StaffAttendanceDay | null {
  if (!raw.date) return null;
  return {
    id: raw.id,
    date: raw.date,
    status: raw.status,
    checkInTime: raw.check_in_time,
    checkOutTime: raw.check_out_time,
    source: raw.source,
    notes: raw.notes ?? null,
  };
}

export function summarizeStaffAttendance(summary: StaffAttendanceSummary): {
  present: number;
  absent: number;
  late: number;
  halfDay: number;
  total: number;
  percentage: number | null;
} {
  const { present, absent, late, half_day: halfDay, total } = summary;
  const marked = present + absent + late + halfDay;
  const percentage = marked > 0 ? (present / marked) * 100 : null;
  return { present, absent, late, halfDay, total, percentage };
}

export function toPayrollSummary(raw: PayrollRecordRow): StaffPayrollSummary {
  return {
    id: raw.id,
    periodLabel: raw.period_name ?? raw.month ?? 'Pay period',
    netSalary: raw.net_salary,
    status: raw.status,
    paymentDate: raw.payment_date ?? null,
  };
}

export function totalLeaveRemaining(balances: StaffLeaveBalanceItem[]): number {
  return balances.reduce((sum, b) => sum + b.remainingDays, 0);
}
