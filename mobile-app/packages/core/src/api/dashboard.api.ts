import { apiClient } from './client';
import type {
  AdminDashboardStats,
  DashboardStatsFilters,
  PaginatedListMeta,
  PendingApprovalsSummary,
} from '../types/dashboard';
import type { ApiResponse } from '../types';

type LeaveListPayload = {
  data?: unknown[];
  total?: number;
};

/**
 * Dashboard API module — reuses existing Laravel endpoints (no new routes in Batch 2).
 *
 * - `GET /dashboard/stats` — enrollment, attendance (present today), collections, outstanding
 * - `GET /leave-requests?status=pending` — pending approval count (paginated total)
 * - `GET /lesson-plans/review-queue` — submitted lesson plans awaiting review
 */
export const dashboardApi = {
  getStats(filters?: DashboardStatsFilters): Promise<ApiResponse<AdminDashboardStats>> {
    const params: Record<string, number> = {};
    if (filters?.academic_year_id != null) {
      params.academic_year_id = filters.academic_year_id;
    }
    if (filters?.term_id != null) {
      params.term_id = filters.term_id;
    }
    return apiClient.get<AdminDashboardStats>('/dashboard/stats', params);
  },

  async getPendingLeaveCount(): Promise<number> {
    const res = await apiClient.get<LeaveListPayload>('/leave-requests', {
      status: 'pending',
      per_page: 1,
    });
    if (!res.success) {
      throw new Error(res.message || 'Failed to load pending leave requests.');
    }
    return res.data?.total ?? 0;
  },

  async getLessonPlanReviewCount(): Promise<number> {
    const res = await apiClient.get<LeaveListPayload>('/lesson-plans/review-queue', {
      per_page: 1,
    });
    if (!res.success) {
      throw new Error(res.message || 'Failed to load lesson plan review queue.');
    }
    return res.data?.total ?? 0;
  },

  /** Aggregates pending leave + lesson-plan reviews (best-effort; 403 on LP → 0). */
  async getPendingApprovalsSummary(): Promise<PendingApprovalsSummary> {
    let pendingLeave = 0;
    let pendingLessonPlans = 0;

    try {
      pendingLeave = await this.getPendingLeaveCount();
    } catch {
      pendingLeave = 0;
    }

    try {
      pendingLessonPlans = await this.getLessonPlanReviewCount();
    } catch {
      pendingLessonPlans = 0;
    }

    return {
      pending_leave_requests: pendingLeave,
      pending_lesson_plans: pendingLessonPlans,
      total: pendingLeave + pendingLessonPlans,
    };
  },
};

export type { PaginatedListMeta };
