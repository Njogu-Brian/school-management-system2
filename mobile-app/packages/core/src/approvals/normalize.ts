import type { ApplicationListRecord, ApplicationStatus } from '../types/admissions';
import type {
  ApprovalCompositeId,
  ApprovalItem,
  ApprovalPriority,
  ApprovalSourceType,
  ApprovalStatus,
  LeaveRequestRecord,
  LessonPlanRecord,
} from '../types/approval';

const ESCALATED_DAYS = 7;
const CRITICAL_LEAVE_DAYS = 2;
const HIGH_LEAVE_DAYS = 7;

function daysUntil(dateIso: string): number {
  const target = new Date(dateIso);
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  target.setHours(0, 0, 0, 0);
  return Math.round((target.getTime() - now.getTime()) / 86400000);
}

function daysSince(dateIso: string): number {
  return -daysUntil(dateIso);
}

export function toCompositeId(
  sourceType: ApprovalSourceType,
  sourceId: number,
): ApprovalCompositeId {
  return `${sourceType}:${sourceId}`;
}

export function parseCompositeId(id: ApprovalCompositeId): {
  sourceType: ApprovalSourceType;
  sourceId: number;
} {
  const [sourceType, raw] = id.split(':') as [ApprovalSourceType, string];
  return { sourceType, sourceId: Number(raw) };
}

function deriveLeaveStatus(leave: LeaveRequestRecord): ApprovalStatus {
  const api = (leave.status || '').toLowerCase();
  if (api === 'approved') return 'approved';
  if (api === 'rejected' || api === 'cancelled') return 'rejected';
  if (api === 'pending') {
    if (daysUntil(leave.end_date) < 0) return 'expired';
    if (daysSince(leave.created_at) >= ESCALATED_DAYS) return 'escalated';
    return 'pending';
  }
  return 'pending';
}

function deriveLessonPlanStatus(lp: LessonPlanRecord): ApprovalStatus {
  const api = (lp.submission_status ?? lp.status ?? 'draft').toLowerCase();
  if (api === 'approved') return 'approved';
  if (api === 'rejected') return 'rejected';
  if (api === 'submitted') {
    if (lp.date && daysUntil(lp.date) < -14) return 'expired';
    if (lp.submitted_at && daysSince(lp.submitted_at) >= ESCALATED_DAYS) return 'escalated';
    return 'pending';
  }
  return 'pending';
}

function deriveLeavePriority(leave: LeaveRequestRecord, status: ApprovalStatus): ApprovalPriority {
  if (status !== 'pending' && status !== 'escalated') return 'low';
  const untilStart = daysUntil(leave.start_date);
  if (untilStart <= CRITICAL_LEAVE_DAYS) return 'critical';
  if (untilStart <= HIGH_LEAVE_DAYS) return 'high';
  if (daysSince(leave.created_at) >= 5) return 'high';
  return 'medium';
}

function deriveLessonPlanPriority(
  lp: LessonPlanRecord,
  status: ApprovalStatus,
): ApprovalPriority {
  if (status !== 'pending' && status !== 'escalated') return 'low';
  if (lp.is_late) return 'critical';
  if (lp.date && daysUntil(lp.date) <= 2) return 'critical';
  if (lp.submitted_at && daysSince(lp.submitted_at) >= 5) return 'high';
  return 'medium';
}

export function leaveToApprovalItem(leave: LeaveRequestRecord): ApprovalItem {
  const status = deriveLeaveStatus(leave);
  const priority = deriveLeavePriority(leave, status);
  const days = leave.days ?? leave.days_count ?? 0;

  return {
    id: toCompositeId('leave_request', leave.id),
    sourceType: 'leave_request',
    sourceId: leave.id,
    title: leave.leave_type_name ?? 'Leave request',
    subtitle: `${leave.staff_name ?? 'Staff'} · ${days} day${days === 1 ? '' : 's'}`,
    status,
    priority,
    requestedAt: leave.created_at,
    dueDate: leave.end_date,
    requesterName: leave.staff_name,
    summary: leave.reason ?? undefined,
    canAct: status === 'pending' || status === 'escalated',
  };
}

function deriveAdmissionStatus(status: ApplicationStatus): ApprovalStatus {
  if (status === 'enrolled') return 'approved';
  if (status === 'rejected') return 'rejected';
  return 'pending';
}

export function admissionToApprovalItem(app: ApplicationListRecord): ApprovalItem {
  const status = deriveAdmissionStatus(app.application_status);
  const classLabel = app.class_name ?? app.preferred_class_name;

  return {
    id: toCompositeId('online_admission', app.id),
    sourceType: 'online_admission',
    sourceId: app.id,
    title: app.full_name,
    subtitle: [classLabel, app.application_status.replace('_', ' ')].filter(Boolean).join(' · '),
    status,
    priority: 'medium',
    requestedAt: app.application_date ?? new Date().toISOString(),
    requesterName: app.full_name,
    summary: app.application_source ?? undefined,
    canAct: false,
  };
}

export function lessonPlanToApprovalItem(lp: LessonPlanRecord): ApprovalItem {
  const status = deriveLessonPlanStatus(lp);
  const priority = deriveLessonPlanPriority(lp, status);

  return {
    id: toCompositeId('lesson_plan', lp.id),
    sourceType: 'lesson_plan',
    sourceId: lp.id,
    title: lp.topic ?? 'Lesson plan',
    subtitle: [
      lp.teacher_name,
      lp.class_name,
      lp.subject_name,
    ]
      .filter(Boolean)
      .join(' · '),
    status,
    priority,
    requestedAt: lp.submitted_at ?? lp.created_at,
    dueDate: lp.date,
    requesterName: lp.teacher_name ?? undefined,
    summary: lp.class_name ? `${lp.class_name}${lp.subject_name ? ` — ${lp.subject_name}` : ''}` : undefined,
    canAct: status === 'pending' || status === 'escalated',
  };
}
