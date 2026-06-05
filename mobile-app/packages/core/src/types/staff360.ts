/** Leave balance row from `GET /staff/{id}/leave-balances`. */
export interface StaffLeaveBalanceRecord {
  id: number;
  leave_type_id: number;
  leave_type_name?: string | null;
  leave_type_code?: string | null;
  academic_year_id: number;
  entitlement_days: number;
  used_days: number;
  remaining_days: number;
  carried_forward: number;
}

export interface StaffLeaveBalancesPayload {
  staff_id: number;
  academic_year: { id: number; name: string } | null;
  balances: StaffLeaveBalanceRecord[];
}

export interface StaffLeaveBalanceItem {
  id: number;
  leaveTypeId: number;
  leaveTypeName: string;
  leaveTypeCode: string | null;
  entitlementDays: number;
  usedDays: number;
  remainingDays: number;
  carriedForward: number;
}

/** Attendance row from `GET /staff/{id}/attendance-history`. */
export interface StaffAttendanceHistoryRow {
  id: number;
  staff_id: number;
  date: string | null;
  status: string;
  check_in_time: string | null;
  check_out_time: string | null;
  check_in_distance_meters?: number | null;
  check_out_distance_meters?: number | null;
  notes?: string | null;
  marked_by?: number | null;
  source: 'clock' | 'manual';
}

export interface StaffAttendanceSummary {
  total: number;
  present: number;
  absent: number;
  late: number;
  half_day: number;
}

export interface StaffAttendanceHistoryPayload {
  staff: { id: number; full_name: string } | null;
  start_date: string;
  end_date: string;
  summary: StaffAttendanceSummary;
  history: {
    data: StaffAttendanceHistoryRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}

export interface StaffAttendanceDay {
  id: number;
  date: string;
  status: string;
  checkInTime: string | null;
  checkOutTime: string | null;
  source: 'clock' | 'manual';
  notes: string | null;
}

/** Payroll row from `GET /payroll-records?staff_id=`. */
export interface PayrollRecordRow {
  id: number;
  staff_id: number;
  staff_name?: string | null;
  staff_employee_number?: string | null;
  month?: string | null;
  period_name?: string | null;
  basic_salary: number;
  allowances: number;
  deductions: number;
  gross_salary: number;
  net_salary: number;
  status: string;
  payment_date?: string | null;
  created_at: string;
  updated_at: string;
}

export interface StaffPayrollSummary {
  id: number;
  periodLabel: string;
  netSalary: number;
  status: string;
  paymentDate: string | null;
}
