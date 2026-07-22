import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export type ConcernCategory =
  | 'financial'
  | 'academic'
  | 'teacher'
  | 'transport'
  | 'meals'
  | 'administration';

export interface ConcernRecord {
  id: number;
  student_id: number;
  student_name?: string | null;
  admission_number?: string | null;
  class_name?: string | null;
  category: ConcernCategory | string;
  description: string;
  status: string;
  staff: Array<{ id: number; name?: string | null }>;
  created_by_name?: string | null;
  created_at?: string | null;
}

export const concernsApi = {
  list(params?: {
    status?: string;
    category?: string;
    search?: string;
    page?: number;
  }): Promise<ApiResponse<PaginatedResponse<ConcernRecord>>> {
    return apiClient.get('/concerns', params);
  },

  get(id: number): Promise<ApiResponse<ConcernRecord>> {
    return apiClient.get(`/concerns/${id}`);
  },

  create(payload: {
    student_id: number;
    category: ConcernCategory | string;
    description: string;
    staff_ids?: number[];
  }): Promise<ApiResponse<ConcernRecord>> {
    return apiClient.post('/concerns', payload);
  },

  update(
    id: number,
    payload: { status?: string; description?: string; staff_ids?: number[] },
  ): Promise<ApiResponse<ConcernRecord>> {
    return apiClient.put(`/concerns/${id}`, payload);
  },
};
