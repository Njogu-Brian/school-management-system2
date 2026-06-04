import { approvalsApi } from '../api/approvals.api';
import type { ApprovalItem, ApprovalListFilters } from '../types/approval';
import { leaveToApprovalItem, lessonPlanToApprovalItem } from './normalize';

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

/** Merges enabled approval sources and applies client-side filters/sort. */
export async function fetchApprovalItems(
  filters: ApprovalListFilters,
  options?: { includeLeave?: boolean; includeLessonPlans?: boolean },
): Promise<ApprovalItem[]> {
  const includeLeave = options?.includeLeave !== false;
  const includeLessonPlans = options?.includeLessonPlans !== false;

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

  const merged = batches.flat().filter((item) => matchesFilters(item, filters));
  return sortApprovals(merged);
}
