import type {
  ApprovalListFilters,
  LeaveRequestRecord,
  LessonPlanRecord,
} from '../types/approval';
import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface LeaveListParams {
  status?: string;
  staff_id?: number;
  page?: number;
  per_page?: number;
}

export interface LessonPlanListParams {
  submission_status?: string;
  page?: number;
  per_page?: number;
}

/**
 * Approval data access — reuses existing Laravel routes (no new endpoints).
 */
export const approvalsApi = {
  listLeaveRequests(
    params?: LeaveListParams,
  ): Promise<ApiResponse<PaginatedResponse<LeaveRequestRecord>>> {
    return apiClient.get<PaginatedResponse<LeaveRequestRecord>>('/leave-requests', params);
  },

  listLessonPlans(
    params?: LessonPlanListParams,
  ): Promise<ApiResponse<PaginatedResponse<LessonPlanRecord>>> {
    return apiClient.get<PaginatedResponse<LessonPlanRecord>>('/lesson-plans', params);
  },

  listLessonPlanReviewQueue(
    params?: { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<LessonPlanRecord>>> {
    return apiClient.get<PaginatedResponse<LessonPlanRecord>>(
      '/lesson-plans/review-queue',
      params,
    );
  },

  getLessonPlan(id: number): Promise<ApiResponse<LessonPlanRecord>> {
    return apiClient.get<LessonPlanRecord>(`/lesson-plans/${id}`);
  },

  approveLeave(id: number, adminNotes?: string): Promise<ApiResponse<LeaveRequestRecord>> {
    return apiClient.post<LeaveRequestRecord>(`/leave-requests/${id}/approve`, {
      admin_notes: adminNotes,
    });
  },

  rejectLeave(id: number, rejectionReason: string): Promise<ApiResponse<LeaveRequestRecord>> {
    return apiClient.post<LeaveRequestRecord>(`/leave-requests/${id}/reject`, {
      rejection_reason: rejectionReason,
    });
  },

  approveLessonPlan(
    id: number,
    approvalNotes?: string,
  ): Promise<ApiResponse<LessonPlanRecord>> {
    return apiClient.post<LessonPlanRecord>(`/lesson-plans/${id}/approve`, {
      approval_notes: approvalNotes,
    });
  },

  rejectLessonPlan(
    id: number,
    rejectionNotes: string,
  ): Promise<ApiResponse<LessonPlanRecord>> {
    return apiClient.post<LessonPlanRecord>(`/lesson-plans/${id}/reject`, {
      rejection_notes: rejectionNotes,
    });
  },

  /** Unified inbox (Sprint 9) — falls back to client merge when unavailable. */
  listUnified(params?: {
    status?: string;
    source_type?: string;
    priority?: string;
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<
    ApiResponse<
      Array<{
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
      }>
    >
  > {
    return apiClient.get('/approvals', params);
  },

  approveUnified(
    compositeId: string,
    notes?: string,
  ): Promise<ApiResponse<unknown>> {
    const encoded = encodeURIComponent(compositeId);
    return apiClient.post(`/approvals/${encoded}/approve`, { admin_notes: notes, approval_notes: notes });
  },

  rejectUnified(
    compositeId: string,
    reason: string,
  ): Promise<ApiResponse<unknown>> {
    const encoded = encodeURIComponent(compositeId);
    return apiClient.post(`/approvals/${encoded}/reject`, {
      rejection_reason: reason,
      rejection_notes: reason,
    });
  },
};

export type { ApprovalListFilters };
