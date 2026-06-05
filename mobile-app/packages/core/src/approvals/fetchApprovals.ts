import { admissionsApi } from '../api/admissions.api';
import { approvalsApi } from '../api/approvals.api';
import type { ApprovalSourceType } from '../types/approval';
import type { ApplicationStatus } from '../types/admissions';
import type { ApprovalItem, ApprovalListFilters } from '../types/approval';
import {
  admissionToApprovalItem,
  leaveToApprovalItem,
  lessonPlanToApprovalItem,
} from './normalize';

const DEFAULT_PER_PAGE = 30;

function matchesFilters(item: ApprovalItem, filters: ApprovalListFilters): boolean {
  if (filters.status && filters.status !== 'all' && item.status !== filters.status) {
    return false;
  }
  if (filters.priority && filters.priority !== 'all' && item.priority !== filters.priority) {
    return false;
  }
  if (filters.sourceType && filters.sourceType !== 'all' && item.sourceType !== filters.sourceType) {
    return false;
  }
  if (filters.search?.trim()) {
    const q = filters.search.trim().toLowerCase();
    const hay = `${item.title} ${item.subtitle} ${item.requesterName ?? ''}`.toLowerCase();
    if (!hay.includes(q)) return false;
  }
  return true;
}

function priorityRank(p: ApprovalItem['priority']): number {
  switch (p) {
    case 'critical':
      return 0;
    case 'high':
      return 1;
    case 'medium':
      return 2;
    default:
      return 3;
  }
}

function sortApprovals(items: ApprovalItem[]): ApprovalItem[] {
  return [...items].sort((a, b) => {
    const pr = priorityRank(a.priority) - priorityRank(b.priority);
    if (pr !== 0) return pr;
    return new Date(b.requestedAt).getTime() - new Date(a.requestedAt).getTime();
  });
}

async function fetchLeaveItems(filters: ApprovalListFilters): Promise<ApprovalItem[]> {
  const status = filters.status ?? 'all';
  let apiStatus: string | undefined;
  if (status === 'approved' || status === 'rejected') {
    apiStatus = status;
  } else if (status === 'pending' || status === 'escalated' || status === 'expired') {
    apiStatus = 'pending';
  }

  const res = await approvalsApi.listLeaveRequests({
    status: apiStatus,
    per_page: filters.perPage ?? DEFAULT_PER_PAGE,
    page: filters.page ?? 1,
  });
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load leave requests.');
  }

  let items = res.data.data.map(leaveToApprovalItem);
  if (status === 'pending') {
    items = items.filter((i) => i.status === 'pending');
  } else if (status === 'escalated') {
    items = items.filter((i) => i.status === 'escalated');
  } else if (status === 'expired') {
    items = items.filter((i) => i.status === 'expired');
  }
  return items;
}

async function fetchLessonPlanItems(filters: ApprovalListFilters): Promise<ApprovalItem[]> {
  const status = filters.status;

  if (
    status === 'pending' ||
    status === 'escalated' ||
    status === 'expired' ||
    status === 'all' ||
    !status
  ) {
    try {
      const res = await approvalsApi.listLessonPlanReviewQueue({
        per_page: filters.perPage ?? DEFAULT_PER_PAGE,
        page: filters.page ?? 1,
      });
      if (res.success && res.data) {
        let items = res.data.data.map(lessonPlanToApprovalItem);
        items = applyLessonPlanStatusFilter(items, status);
        return items;
      }
    } catch {
      // Reviewer may lack permission — fall through to filtered index.
    }
  }

  let submissionStatus: string | undefined;
  if (status === 'approved') submissionStatus = 'approved';
  else if (status === 'rejected') submissionStatus = 'rejected';
  else if (status === 'pending' || status === 'escalated' || status === 'expired') {
    submissionStatus = 'submitted';
  }

  const res = await approvalsApi.listLessonPlans({
    submission_status: submissionStatus,
    per_page: filters.perPage ?? DEFAULT_PER_PAGE,
    page: filters.page ?? 1,
  });
  if (!res.success || !res.data) {
    throw new Error(res.message || 'Failed to load lesson plans.');
  }
  let items = res.data.data.map(lessonPlanToApprovalItem);
  items = applyLessonPlanStatusFilter(items, status);
  return items;
}

function applyLessonPlanStatusFilter(
  items: ApprovalItem[],
  status: ApprovalListFilters['status'],
): ApprovalItem[] {
  if (!status || status === 'all') return items;
  if (status === 'pending') return items.filter((i) => i.status === 'pending');
  if (status === 'escalated') return items.filter((i) => i.status === 'escalated');
  if (status === 'expired') return items.filter((i) => i.status === 'expired');
  return items;
}

function admissionStatusesForFilter(
  status: ApprovalListFilters['status'],
): ApplicationStatus[] | 'all' {
  if (!status || status === 'all') return 'all';
  if (status === 'approved') return ['enrolled'];
  if (status === 'rejected') return ['rejected'];
  if (status === 'pending' || status === 'escalated' || status === 'expired') {
    return ['pending', 'under_review', 'waitlisted'];
  }
  return 'all';
}

async function fetchAdmissionItems(filters: ApprovalListFilters): Promise<ApprovalItem[]> {
  const mapped = admissionStatusesForFilter(filters.status);
  const perPage = filters.perPage ?? DEFAULT_PER_PAGE;
  const page = filters.page ?? 1;

  const statuses: ApplicationStatus[] =
    mapped === 'all'
      ? ['pending', 'under_review', 'waitlisted', 'enrolled', 'rejected']
      : mapped;

  const results = await Promise.all(
    statuses.map((applicationStatus) =>
      admissionsApi.list({ status: applicationStatus, per_page: perPage, page }),
    ),
  );

  const items: ApprovalItem[] = [];
  for (const res of results) {
    if (res.success && res.data) {
      items.push(...res.data.data.map(admissionToApprovalItem));
    }
  }

  const status = filters.status;
  if (status === 'pending') {
    return items.filter((i) => i.status === 'pending');
  }
  if (status === 'approved') {
    return items.filter((i) => i.status === 'approved');
  }
  if (status === 'rejected') {
    return items.filter((i) => i.status === 'rejected');
  }
  return items;
}

function mapUnifiedRow(row: {
  id: string;
  source_type: string;
  source_id: number;
  title: string;
  subtitle: string;
  status: string;
  priority: string;
  requested_at: string;
  due_date?: string;
  requester_name?: string;
  summary?: string;
  can_act: boolean;
}): ApprovalItem {
  return {
    id: row.id as ApprovalItem['id'],
    sourceType: row.source_type as ApprovalSourceType,
    sourceId: row.source_id,
    title: row.title,
    subtitle: row.subtitle,
    status: row.status as ApprovalItem['status'],
    priority: row.priority as ApprovalItem['priority'],
    requestedAt: row.requested_at,
    dueDate: row.due_date,
    requesterName: row.requester_name,
    summary: row.summary,
    canAct: row.can_act,
  };
}

async function fetchUnifiedItems(filters: ApprovalListFilters): Promise<ApprovalItem[] | null> {
  try {
    const res = await approvalsApi.listUnified({
      status: filters.status === 'all' ? undefined : filters.status,
      source_type: filters.sourceType === 'all' ? undefined : filters.sourceType,
      priority: filters.priority === 'all' ? undefined : filters.priority,
      search: filters.search,
      per_page: filters.perPage,
      page: filters.page,
    });
    if (!res.success || !res.data) return null;
    return res.data.map(mapUnifiedRow).filter((item) => matchesFilters(item, filters));
  } catch {
    return null;
  }
}

/** Prefer `GET /approvals`; fall back to per-domain merge. */
export async function fetchApprovalItems(
  filters: ApprovalListFilters,
  options?: {
    includeLeave?: boolean;
    includeLessonPlans?: boolean;
    includeAdmissions?: boolean;
  },
): Promise<ApprovalItem[]> {
  const unified = await fetchUnifiedItems(filters);
  if (unified) {
    return sortApprovals(unified);
  }

  const includeLeave = options?.includeLeave !== false;
  const includeLessonPlans = options?.includeLessonPlans !== false;
  const includeAdmissions = options?.includeAdmissions !== false;

  const batches: ApprovalItem[][] = [];
  if (includeLeave && (!filters.sourceType || filters.sourceType === 'all' || filters.sourceType === 'leave_request')) {
    batches.push(await fetchLeaveItems(filters));
  }
  if (
    includeLessonPlans &&
    (!filters.sourceType || filters.sourceType === 'all' || filters.sourceType === 'lesson_plan')
  ) {
    batches.push(await fetchLessonPlanItems(filters));
  }
  if (
    includeAdmissions &&
    (!filters.sourceType || filters.sourceType === 'all' || filters.sourceType === 'online_admission')
  ) {
    batches.push(await fetchAdmissionItems(filters));
  }

  const merged = batches.flat().filter((item) => matchesFilters(item, filters));
  return sortApprovals(merged);
}
