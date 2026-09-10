import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface SpeedTestEntry {
  id: number;
  student_id: number;
  student_name: string;
  score: number | null;
  out_of: number | null;
  score_percent: number | null;
}

export interface SpeedTestBatch {
  batch_key: string;
  title: string;
  classroom_id: number;
  classroom_name: string | null;
  subject_id: number;
  subject_name: string | null;
  question_count: number | null;
  max_marks: number | null;
  assessment_date: string | null;
  student_count: number;
  marked_count: number;
  entries?: SpeedTestEntry[];
}

export const speedTestsApi = {
  list(params?: { classroom_id?: number; student_id?: number }): Promise<ApiResponse<SpeedTestBatch[]>> {
    const query: Record<string, number> = {};
    if (params?.classroom_id != null) query.classroom_id = params.classroom_id;
    if (params?.student_id != null) query.student_id = params.student_id;
    return apiClient.get<SpeedTestBatch[]>('/speed-tests', query);
  },

  create(payload: {
    classroom_id: number;
    subject_id: number;
    question_count: number;
    max_marks: number;
    title?: string;
    assessment_date?: string;
  }): Promise<ApiResponse<SpeedTestBatch>> {
    return apiClient.post<SpeedTestBatch>('/speed-tests', payload);
  },

  get(batchKey: string): Promise<ApiResponse<SpeedTestBatch>> {
    return apiClient.get<SpeedTestBatch>(`/speed-tests/${batchKey}`);
  },

  saveMarks(
    batchKey: string,
    entries: Array<{ student_id: number; score: number | null }>,
  ): Promise<ApiResponse<SpeedTestBatch>> {
    return apiClient.put<SpeedTestBatch>(`/speed-tests/${batchKey}/marks`, { entries });
  },
};
