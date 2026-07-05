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
    performanceReviews: (id: number) =>
      [...queryKeys.staff.all, 'performance-reviews', id] as const,
    performanceReview: (staffId: number, reviewId: number) =>
      [...queryKeys.staff.all, 'performance-review', staffId, reviewId] as const,
    trainingRecords: (id: number) =>
      [...queryKeys.staff.all, 'training-records', id] as const,
    trainingRecord: (staffId: number, recordId: number) =>
      [...queryKeys.staff.all, 'training-record', staffId, recordId] as const,
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
    examClassSheet: (params: Record<string, unknown>) =>
      [...queryKeys.academics.all, 'exam-class-sheet', params] as const,
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
    studentFinanceSearch: (q: string) =>
      [...queryKeys.finance.all, 'student-search', q] as const,
    paymentLink: (studentId: number) =>
      [...queryKeys.finance.all, 'payment-link', studentId] as const,
  },
  operations: {
    all: ['operations'] as const,
    summary: () => [...queryKeys.operations.all, 'summary'] as const,
    routes: (search?: string) => [...queryKeys.operations.all, 'routes', search ?? ''] as const,
    route: (id: number) => [...queryKeys.operations.all, 'route', id] as const,
    vehicles: (search?: string) => [...queryKeys.operations.all, 'vehicles', search ?? ''] as const,
    vehicle: (id: number) => [...queryKeys.operations.all, 'vehicle', id] as const,
    studentRequirements: (studentId: number) =>
      [...queryKeys.operations.all, 'student-requirements', studentId] as const,
    inventory: (filters?: { search?: string; lowStock?: boolean }) =>
      [...queryKeys.operations.all, 'inventory', filters ?? {}] as const,
    requisitions: (status?: string) =>
      [...queryKeys.operations.all, 'requisitions', status ?? 'all'] as const,
    requisition: (id: number) => [...queryKeys.operations.all, 'requisition', id] as const,
    visitors: (filters?: { onSite?: boolean; date?: string }) =>
      [...queryKeys.operations.all, 'visitors', filters ?? {}] as const,
    visitor: (id: number) => [...queryKeys.operations.all, 'visitor', id] as const,
    assets: (filters?: { search?: string; status?: string }) =>
      [...queryKeys.operations.all, 'assets', filters ?? {}] as const,
    asset: (id: number) => [...queryKeys.operations.all, 'asset', id] as const,
    medicalRecords: (studentId: number) =>
      [...queryKeys.operations.all, 'medical-records', studentId] as const,
    inventoryItem: (id: number) => [...queryKeys.operations.all, 'inventory-item', id] as const,
    requirementsStudents: (search?: string) =>
      [...queryKeys.operations.all, 'requirements-students', search ?? ''] as const,
    libraryBooks: (search?: string) =>
      [...queryKeys.operations.all, 'library-books', search ?? ''] as const,
    borrowings: (filters?: { status?: string; search?: string; studentId?: number }) =>
      [...queryKeys.operations.all, 'borrowings', filters ?? {}] as const,
  },
  documents: {
    all: ['documents'] as const,
    student: (studentId: number) => [...queryKeys.documents.all, 'student', studentId] as const,
    staff: (staffId: number) => [...queryKeys.documents.all, 'staff', staffId] as const,
  },
  communication: {
    all: ['communication'] as const,
    announcements: (page?: number | 'infinite') =>
      [...queryKeys.communication.all, 'announcements', page ?? 1] as const,
    announcement: (id: number) => [...queryKeys.communication.all, 'announcement', id] as const,
    templates: (type?: string) =>
      [...queryKeys.communication.all, 'templates', type ?? 'sms'] as const,
    template: (id: number) => [...queryKeys.communication.all, 'template', id] as const,
    logs: (filters?: { channel?: string; status?: string; infinite?: boolean }) =>
      [...queryKeys.communication.all, 'logs', filters ?? {}] as const,
    log: (id: number) => [...queryKeys.communication.all, 'log', id] as const,
    recipients: (classroomId?: number) =>
      [...queryKeys.communication.all, 'recipients', classroomId ?? 'all'] as const,
  },
  notifications: {
    all: ['notifications'] as const,
    unreadCount: () => [...queryKeys.notifications.all, 'unread-count'] as const,
    list: (filters?: { isRead?: boolean; category?: string; search?: string }) =>
      [...queryKeys.notifications.all, 'list', filters ?? {}] as const,
  },
  staffClock: {
    all: ['staff-clock'] as const,
    today: () => [...queryKeys.staffClock.all, 'today'] as const,
    history: () => [...queryKeys.staffClock.all, 'history'] as const,
    geofence: () => [...queryKeys.staffClock.all, 'geofence'] as const,
    roster: () => [...queryKeys.staffClock.all, 'roster'] as const,
    memberHistory: (staffId: number) => [...queryKeys.staffClock.all, 'member-history', staffId] as const,
  },
  teacherTransport: {
    all: ['teacher-transport'] as const,
    students: (date?: string) => [...queryKeys.teacherTransport.all, 'students', date ?? 'today'] as const,
    vehicles: () => [...queryKeys.teacherTransport.all, 'vehicles'] as const,
  },
  driverTransport: {
    all: ['driver-transport'] as const,
    trips: (date?: string) => [...queryKeys.driverTransport.all, 'trips', date ?? 'today'] as const,
    trip: (id: number, date?: string) => [...queryKeys.driverTransport.all, 'trip', id, date ?? 'today'] as const,
  },
  reports: {
    all: ['reports'] as const,
    weekly: (weekEnding?: string) =>
      [...queryKeys.reports.all, 'weekly', weekEnding ?? 'recent'] as const,
    weeklyDetail: (type: string, id: number) =>
      [...queryKeys.reports.all, 'weekly-detail', type, id] as const,
    expenses: (range?: { from?: string; to?: string }) =>
      [...queryKeys.reports.all, 'expenses', range ?? {}] as const,
    expensesList: (filters?: { status?: string; search?: string }) =>
      [...queryKeys.reports.all, 'expenses-list', filters ?? {}] as const,
    expenseDetail: (id: number) => [...queryKeys.reports.all, 'expense', id] as const,
    incomeStatement: (months?: number) =>
      [...queryKeys.reports.all, 'income-statement', months ?? 6] as const,
    ledgerPostings: (filters?: { account?: string; drCr?: string; from?: string; to?: string }) =>
      [...queryKeys.reports.all, 'ledger-postings', filters ?? {}] as const,
    trialBalance: (range?: { from?: string; to?: string }) =>
      [...queryKeys.reports.all, 'trial-balance', range ?? {}] as const,
    balanceSheet: () => [...queryKeys.reports.all, 'balance-sheet'] as const,
    boardPack: () => [...queryKeys.reports.all, 'board-pack'] as const,
  },
  search: {
    all: ['search'] as const,
    global: (query: string, module: string) =>
      [...queryKeys.search.all, 'global', query, module] as const,
    suggest: (query: string) => [...queryKeys.search.all, 'suggest', query] as const,
    cached: (query: string) => [...queryKeys.search.all, 'cached', query] as const,
  },
  audit: {
    all: ['audit'] as const,
    list: (filters?: object) => [...queryKeys.audit.all, 'list', filters ?? {}] as const,
    detail: (id: string) => [...queryKeys.audit.all, 'detail', id] as const,
  },
  sessions: {
    all: ['sessions'] as const,
    list: () => [...queryKeys.sessions.all, 'list'] as const,
  },
  analytics: {
    all: ['analytics'] as const,
    executive: (period: string) => [...queryKeys.analytics.all, 'executive', period] as const,
  },
  cbc: {
    all: ['cbc'] as const,
    learningAreas: () => [...queryKeys.cbc.all, 'learning-areas'] as const,
    strands: (learningAreaId?: number) =>
      [...queryKeys.cbc.all, 'strands', learningAreaId ?? 'all'] as const,
    substrands: (strandId?: number) =>
      [...queryKeys.cbc.all, 'substrands', strandId ?? 'all'] as const,
    substrand: (id: number) => [...queryKeys.cbc.all, 'substrand', id] as const,
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
