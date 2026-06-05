import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface PerformanceReviewRow {
  id: number;
  review_type?: string | null;
  review_date?: string | null;
  overall_rating?: number | null;
  status?: string | null;
  reviewer_name?: string | null;
}

export interface TrainingRecordRow {
  id: number;
  training_name: string;
  provider?: string | null;
  start_date?: string | null;
  end_date?: string | null;
  training_type?: string | null;
  status?: string | null;
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
};
