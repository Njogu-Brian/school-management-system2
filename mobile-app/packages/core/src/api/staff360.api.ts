import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface PerformanceReviewRow {
  id: number;
  staff_id?: number;
  review_type?: string | null;
  review_period_start?: string | null;
  review_period_end?: string | null;
  review_date?: string | null;
  overall_rating?: number | null;
  status?: string | null;
  reviewer_name?: string | null;
  acknowledged_at?: string | null;
  category_ratings?: Record<string, unknown> | null;
  strengths?: string | null;
  areas_for_improvement?: string | null;
  achievements?: string | null;
  goals_met?: string | null;
  comments?: string | null;
  reviewer_comments?: string | null;
}

export interface TrainingRecordRow {
  id: number;
  staff_id?: number;
  training_name: string;
  provider?: string | null;
  location?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  duration_hours?: number | null;
  training_type?: string | null;
  status?: string | null;
  certificate_number?: string | null;
  cost?: number | null;
  description?: string | null;
  objectives?: string | null;
  outcomes?: string | null;
  certificate_file?: string | null;
}

export const staff360Api = {
  listPerformanceReviews(
    staffId: number,
    params?: { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<PerformanceReviewRow> & { staff_id: number }>> {
    return apiClient.get(`/staff/${staffId}/performance-reviews`, params);
  },

  listTrainingRecords(
    staffId: number,
    params?: { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<TrainingRecordRow> & { staff_id: number }>> {
    return apiClient.get(`/staff/${staffId}/training-records`, params);
  },

  getPerformanceReview(staffId: number, id: number): Promise<ApiResponse<PerformanceReviewRow>> {
    return apiClient.get<PerformanceReviewRow>(`/staff/${staffId}/performance-reviews/${id}`);
  },

  getTrainingRecord(staffId: number, id: number): Promise<ApiResponse<TrainingRecordRow>> {
    return apiClient.get<TrainingRecordRow>(`/staff/${staffId}/training-records/${id}`);
  },
};
