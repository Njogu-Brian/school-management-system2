import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface HomeworkAssignment {
  id: number;
  title: string;
  instructions?: string | null;
  due_date?: string | null;
  classroom_id?: number | null;
  class_name?: string | null;
  stream_id?: number | null;
  stream_name?: string | null;
  subject_id?: number | null;
  subject_name?: string | null;
  teacher_id?: number | null;
  teacher_name?: string | null;
  max_score?: number | null;
  status?: string | null;
  created_at?: string | null;
}

export const homeworkApi = {
  list(params?: {
    classroom_id?: number;
    class_id?: number;
    subject_id?: number;
    teacher_id?: number;
    status?: string;
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<HomeworkAssignment>>> {
    return apiClient.get('/assignments', params);
  },

  get(id: number): Promise<ApiResponse<HomeworkAssignment>> {
    return apiClient.get(`/assignments/${id}`);
  },

  create(payload: {
    title: string;
    instructions?: string;
    due_date: string;
    classroom_id: number;
    stream_id?: number | null;
    subject_id: number;
    target_scope?: 'class' | 'stream';
    max_score?: number;
  }): Promise<ApiResponse<HomeworkAssignment>> {
    return apiClient.post('/assignments', payload);
  },
};
