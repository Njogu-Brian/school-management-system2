/** Normalized approval lifecycle (client + API mapping). */
export type ApprovalStatus =
  | 'pending'
  | 'approved'
  | 'rejected'
  | 'escalated'
  | 'expired';

export type ApprovalPriority = 'critical' | 'high' | 'medium' | 'low';

export type ApprovalSourceType = 'leave_request' | 'lesson_plan' | 'online_admission';

/** Composite id: `{sourceType}:{numericId}` */
export type ApprovalCompositeId = `${ApprovalSourceType}:${number}`;

export interface ApprovalItem {
  id: ApprovalCompositeId;
  sourceType: ApprovalSourceType;
  sourceId: number;
  title: string;
  subtitle: string;
  status: ApprovalStatus;
  priority: ApprovalPriority;
  requestedAt: string;
  /** Display due / effective date (leave end, lesson planned date). */
  dueDate?: string;
  requesterName?: string;
  summary?: string;
  /** Whether approve/reject actions are allowed in the mobile shell. */
  canAct: boolean;
}

export interface ApprovalListFilters {
  status?: ApprovalStatus | 'all';
  priority?: ApprovalPriority | 'all';
  sourceType?: ApprovalSourceType | 'all';
  search?: string;
  page?: number;
  perPage?: number;
}

export interface LeaveRequestRecord {
  id: number;
  staff_id: number;
  staff_name?: string;
  leave_type?: string;
  leave_type_name?: string;
  leave_type_id?: number;
  start_date: string;
  end_date: string;
  days?: number;
  days_count?: number;
  reason?: string | null;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface LessonPlanRecord {
  id: number;
  teacher_id?: number;
  teacher_name?: string | null;
  subject_name?: string | null;
  class_name?: string | null;
  topic?: string;
  date?: string;
  status?: string;
  submission_status?: string;
  is_late?: boolean;
  submitted_at?: string | null;
  created_at: string;
  updated_at: string;
  approval_notes?: string | null;
  rejection_notes?: string | null;
}
