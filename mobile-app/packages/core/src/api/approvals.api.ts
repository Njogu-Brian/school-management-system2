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
};

export type { ApprovalListFilters };
