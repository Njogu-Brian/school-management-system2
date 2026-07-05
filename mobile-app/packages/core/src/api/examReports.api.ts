import type { ApiResponse } from '../types/api';
import type { ExamClassSheetRecord } from '../types/academics';
import { apiClient } from './client';

export const examReportsApi = {
  getClassSheet(params: {
    mode?: 'exam' | 'term';
    exam_id?: number;
    academic_year_id?: number;
    term_id?: number;
    classroom_id: number;
    stream_id?: number;
  }): Promise<ApiResponse<ExamClassSheetRecord>> {
    const query: Record<string, string | number> = {
      classroom_id: params.classroom_id,
    };
    if (params.mode) query.mode = params.mode;
    if (params.exam_id != null) query.exam_id = params.exam_id;
    if (params.academic_year_id != null) query.academic_year_id = params.academic_year_id;
    if (params.term_id != null) query.term_id = params.term_id;
    if (params.stream_id != null) query.stream_id = params.stream_id;
    return apiClient.get<ExamClassSheetRecord>('/reports/exams/class-sheet', query);
  },
};
