/** `GET /students/{id}/stats` */
export interface StudentStatsRecord {
  attendance_percentage: number | null;
  expected_school_days?: number;
  attendance_records_count?: number;
  attendance_days_marked?: number;
  fees_balance?: number;
  exam_average?: number | null;
}

/** `GET /students/{id}/attendance-calendar` day row */
export interface AttendanceCalendarDay {
  date: string;
  status: string;
  is_excused?: boolean;
}

export interface AttendanceSummary {
  present: number;
  absent: number;
  late: number;
  excused: number;
  marked: number;
  percentage: number | null;
}

/** Weekly bucket for attendance trend chart (client-derived). */
export interface AttendanceTrendPoint {
  label: string;
  present: number;
  absent: number;
  late: number;
}

export interface StatementTransactionRecord {
  id: number;
  date: string;
  type: 'invoice' | 'payment' | string;
  /** Real invoice or payment id (statement row ids are offset). */
  entity_id?: number;
  entity_type?: 'invoice' | 'payment' | string;
  reference: string;
  description: string;
  votehead?: string | null;
  debit: number;
  credit: number;
  balance: number;
}

/** `GET /students/search?q=` — includes siblings for finance assign/share. */
export interface StudentFinanceSearchSibling {
  id: number;
  full_name: string;
  admission_number: string;
  classroom_name: string | null;
  stream_name: string | null;
  class_display: string | null;
}

export interface StudentFinanceSearchResult {
  id: number;
  full_name: string;
  admission_number: string;
  classroom_name: string | null;
  stream_name: string | null;
  class_display: string | null;
  label: string;
  siblings: StudentFinanceSearchSibling[];
}

/** `GET /students/{id}/statement` */
export interface StudentStatementRecord {
  student: {
    id: number;
    full_name: string;
    admission_number: string;
    class_name: string;
  };
  year?: number;
  detailed?: boolean;
  opening_balance: number;
  total_invoiced: number;
  total_paid: number;
  closing_balance: number;
  transactions: StatementTransactionRecord[];
}

export type {
  StudentEmergencyContact,
  StudentGuardianContact,
  StudentParentInfo,
} from './student';

/** Timeline row (overview recent activity). */
export interface StudentTimelineEvent {
  id: string;
  title: string;
  subtitle?: string;
  occurredAt: string;
  kind: 'payment' | 'invoice' | 'enrollment' | 'update' | 'other';
}

export type Student360TabId =
  | 'overview'
  | 'attendance'
  | 'academics'
  | 'fees'
  | 'family'
  | 'health'
  | 'transport'
  | 'requirements'
  | 'documents';
