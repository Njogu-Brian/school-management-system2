import type { ApiResponse, PaginatedResponse } from '../types/api';
import type {
  AdmissionsStatsRecord,
  ApplicationDetailRecord,
  ApplicationListFilters,
  ApplicationListRecord,
  EnrollApplicationPayload,
  EnrollApplicationResult,
  UpdateApplicationStatusPayload,
} from '../types/admissions';
import { apiClient } from './client';

/**
 * Admissions workspace APIs (Sprint 5).
 */
export const admissionsApi = {
  getStats(): Promise<ApiResponse<AdmissionsStatsRecord>> {
    return apiClient.get<AdmissionsStatsRecord>('/admissions/stats');
  },

  list(
    params?: ApplicationListFilters,
  ): Promise<ApiResponse<PaginatedResponse<ApplicationListRecord>>> {
    const query: Record<string, string | number | boolean> = {};
    if (params?.search) query.search = params.search;
    if (params?.status) query.status = params.status;
    if (params?.waitlist_only) query.waitlist_only = true;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<ApplicationListRecord>>('/admissions', query);
  },

  getById(id: number): Promise<ApiResponse<ApplicationDetailRecord>> {
    return apiClient.get<ApplicationDetailRecord>(`/admissions/${id}`);
  },

  updateStatus(
    id: number,
    payload: UpdateApplicationStatusPayload,
  ): Promise<ApiResponse<ApplicationDetailRecord>> {
    return apiClient.put<ApplicationDetailRecord>(`/admissions/${id}/status`, payload);
  },

  waitlist(
    id: number,
    reviewNotes?: string | null,
  ): Promise<ApiResponse<ApplicationDetailRecord>> {
    return apiClient.post<ApplicationDetailRecord>(`/admissions/${id}/waitlist`, {
      review_notes: reviewNotes ?? null,
    });
  },

  reject(id: number): Promise<ApiResponse<ApplicationDetailRecord>> {
    return apiClient.post<ApplicationDetailRecord>(`/admissions/${id}/reject`);
  },

  enroll(
    id: number,
    payload: EnrollApplicationPayload,
  ): Promise<ApiResponse<EnrollApplicationResult>> {
    return apiClient.post<EnrollApplicationResult>(`/admissions/${id}/enroll`, payload);
  },
};
