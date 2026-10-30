import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export type ConcernCategory =
  | 'financial'
  | 'academic'
  | 'teacher'
  | 'transport'
  | 'meals'
  | 'administration';

export interface ConcernMessage {
  id: number;
  body: string;
  is_staff: boolean;
  user_id?: number | null;
  author_name?: string | null;
  created_at?: string | null;
}

export interface ConcernRaisedBy {
  name?: string | null;
  slot?: 'mother' | 'father' | 'guardian' | null;
  label: string;
}

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
  raised_by?: ConcernRaisedBy | null;
  raised_by_label?: string | null;
  created_by_name?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
  can_reply?: boolean;
  messages?: ConcernMessage[];
}

export type CreateConcernPayload = {
  student_id?: number;
  student_ids?: number[];
  category: ConcernCategory | string;
  description: string;
  staff_ids: number[];
};

export const concernsApi = {
  list(params?: {
    status?: string;
    category?: string;
    search?: string;
    staff_id?: number;
    page?: number;
  }): Promise<ApiResponse<PaginatedResponse<ConcernRecord>>> {
    return apiClient.get('/concerns', params);
  },

  get(id: number): Promise<ApiResponse<ConcernRecord>> {
    return apiClient.get(`/concerns/${id}`);
  },

  create(payload: CreateConcernPayload): Promise<ApiResponse<ConcernRecord | ConcernRecord[]>> {
    return apiClient.post('/concerns', payload);
  },

  staffOptions(search: string): Promise<
    ApiResponse<Array<{ id: number; full_name: string; employee_number?: string | null; job_title?: string | null }>>
  > {
    return apiClient.get('/concerns/staff-options', { search });
  },

  update(
    id: number,
    payload: { status?: string; description?: string; staff_ids?: number[] },
  ): Promise<ApiResponse<ConcernRecord>> {
    return apiClient.put(`/concerns/${id}`, payload);
  },

  postMessage(id: number, body: string): Promise<ApiResponse<ConcernRecord>> {
    return apiClient.post(`/concerns/${id}/messages`, { body });
  },
};
