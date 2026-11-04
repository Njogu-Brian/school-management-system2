import type { ApiResponse } from '../types/api';
import type {
  ExamClassSheetRecord,
  SubjectAnalysisPayload,
  TeachingScopePayload,
} from '../types/academics';
import { apiClient } from './client';

export const examReportsApi = {
  getClassSheet(params: {
    mode?: 'exam' | 'term' | 'exam_session';
    exam_id?: number;
    exam_session_id?: number;
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
    if (params.exam_session_id != null) query.exam_session_id = params.exam_session_id;
    if (params.academic_year_id != null) query.academic_year_id = params.academic_year_id;
    if (params.term_id != null) query.term_id = params.term_id;
    if (params.stream_id != null) query.stream_id = params.stream_id;
    return apiClient.get<ExamClassSheetRecord>('/reports/exams/class-sheet', query);
  },

  getMyTeachingScope(params?: {
    academic_year_id?: number;
    term_id?: number;
  }): Promise<ApiResponse<TeachingScopePayload>> {
    const query: Record<string, number> = {};
    if (params?.academic_year_id != null) query.academic_year_id = params.academic_year_id;
    if (params?.term_id != null) query.term_id = params.term_id;
    return apiClient.get<TeachingScopePayload>('/reports/exams/my-teaching-scope', query);
  },

  getMySubjectAnalysis(params: {
    classroom_id: number;
    subject_id: number;
    academic_year_id: number;
    term_id: number;
    mode?: 'term' | 'exam_type' | 'by_exam_type';
    exam_type_id?: number;
    stream_id?: number;
  }): Promise<ApiResponse<SubjectAnalysisPayload>> {
    const query: Record<string, string | number> = {
      classroom_id: params.classroom_id,
      subject_id: params.subject_id,
      academic_year_id: params.academic_year_id,
      term_id: params.term_id,
      mode: params.mode ?? 'term',
    };
    if (params.exam_type_id != null) query.exam_type_id = params.exam_type_id;
    if (params.stream_id != null) query.stream_id = params.stream_id;
    return apiClient.get<SubjectAnalysisPayload>('/reports/exams/my-subject-analysis', query);
  },
};
