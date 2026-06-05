import type { ApplicationListFilters } from '../types/admissions';
import type { ApprovalListFilters } from '../types/approval';
import type { DashboardStatsFilters } from '../types/dashboard';
import type {
  ExamListFilters,
  LessonPlanQueueFilters,
  MarksListFilters,
  MarksMatrixFilters,
} from '../types/academics';
import type {
  FinanceTransactionListFilters,
  InvoiceListFilters,
  PaymentListFilters,
} from '../types/finance';
import type { StaffListFilters } from '../types/staff';
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
    academicSummary: (id: number, scope?: { termId?: number; academicYearId?: number }) =>
      [...queryKeys.students.all, 'academic-summary', id, scope ?? {}] as const,
    assessmentHistory: (
      id: number,
      filters?: {
        category?: string;
        subjectId?: number | null;
        termId?: number | null;
        academicYearId?: number | null;
      },
    ) => [...queryKeys.students.all, 'assessment-history', id, filters ?? {}] as const,
    reportCards: (id: number) => [...queryKeys.students.all, 'report-cards', id] as const,
    reportCardDetail: (id: number) => [...queryKeys.students.all, 'report-card', id] as const,
  },
  staff: {
    all: ['staff'] as const,
    list: (filters?: StaffListFilters) =>
      [...queryKeys.staff.all, 'list', filters ?? {}] as const,
    detail: (id: number) => [...queryKeys.staff.all, 'detail', id] as const,
    filterOptions: () => [...queryKeys.staff.all, 'filter-options'] as const,
    leaveBalances: (id: number) => [...queryKeys.staff.all, 'leave-balances', id] as const,
    leaveRequests: (id: number, status?: string) =>
      [...queryKeys.staff.all, 'leave-requests', id, status ?? 'all'] as const,
    attendanceHistory: (
      id: number,
      range?: { startDate?: string; endDate?: string; page?: number },
    ) => [...queryKeys.staff.all, 'attendance-history', id, range ?? {}] as const,
    payrollRecords: (id: number, page?: number) =>
      [...queryKeys.staff.all, 'payroll-records', id, page ?? 1] as const,
  },
  admissions: {
    all: ['admissions'] as const,
    stats: () => [...queryKeys.admissions.all, 'stats'] as const,
    list: (filters?: ApplicationListFilters) =>
      [...queryKeys.admissions.all, 'list', filters ?? {}] as const,
    detail: (id: number) => [...queryKeys.admissions.all, 'detail', id] as const,
  },
  academics: {
    all: ['academics'] as const,
    dashboard: () => [...queryKeys.academics.all, 'dashboard'] as const,
    exams: (filters?: ExamListFilters) =>
      [...queryKeys.academics.all, 'exams', filters ?? {}] as const,
    examDetail: (id: number) => [...queryKeys.academics.all, 'exam', id] as const,
    examMarkingOptions: (id: number) =>
      [...queryKeys.academics.all, 'exam-marking-options', id] as const,
    marks: (filters?: MarksListFilters | Record<string, never>) =>
      [...queryKeys.academics.all, 'marks', filters ?? {}] as const,
    marksMatrixContext: (classroomId?: number) =>
      [...queryKeys.academics.all, 'marks-matrix-context', classroomId ?? 'all'] as const,
    marksMatrix: (filters?: MarksMatrixFilters | Record<string, never>) =>
      [...queryKeys.academics.all, 'marks-matrix', filters ?? {}] as const,
    assessmentHistory: (
      studentId: number,
      filters?: {
        category?: string;
        subjectId?: number | null;
        termId?: number | null;
        academicYearId?: number | null;
      },
    ) => [...queryKeys.academics.all, 'assessment-history', studentId, filters ?? {}] as const,
    reportCards: (studentId: number) =>
      [...queryKeys.academics.all, 'report-cards', studentId] as const,
    reportCardDetail: (id: number) => [...queryKeys.academics.all, 'report-card', id] as const,
    moderationQueue: (filters?: LessonPlanQueueFilters) =>
      [...queryKeys.academics.all, 'moderation-queue', filters ?? {}] as const,
    lessonPlanDetail: (id: number) => [...queryKeys.academics.all, 'lesson-plan', id] as const,
  },
  finance: {
    all: ['finance'] as const,
    dashboard: () => [...queryKeys.finance.all, 'dashboard'] as const,
    invoices: (filters?: InvoiceListFilters) =>
      [...queryKeys.finance.all, 'invoices', filters ?? {}] as const,
    invoiceDetail: (id: number) => [...queryKeys.finance.all, 'invoice', id] as const,
    payments: (filters?: PaymentListFilters) =>
      [...queryKeys.finance.all, 'payments', filters ?? {}] as const,
    paymentDetail: (id: number) => [...queryKeys.finance.all, 'payment', id] as const,
    transactions: (filters?: FinanceTransactionListFilters) =>
      [...queryKeys.finance.all, 'transactions', filters ?? {}] as const,
    transactionDetail: (id: number, type: string) =>
      [...queryKeys.finance.all, 'transaction', id, type] as const,
  },
  settings: {
    all: ['settings'] as const,
    school: () => [...queryKeys.settings.all, 'school'] as const,
    academicYears: () => [...queryKeys.settings.all, 'academic-years'] as const,
    terms: (academicYearId?: number) =>
      [...queryKeys.settings.all, 'terms', academicYearId ?? 'all'] as const,
    classes: () => [...queryKeys.settings.all, 'classes'] as const,
    streams: (classId: number) => [...queryKeys.settings.all, 'streams', classId] as const,
    subjects: () => [...queryKeys.settings.all, 'subjects'] as const,
    grading: () => [...queryKeys.settings.all, 'grading'] as const,
    roles: () => [...queryKeys.settings.all, 'roles'] as const,
  },
};
