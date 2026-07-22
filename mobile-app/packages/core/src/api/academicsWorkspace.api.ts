import type { ApiResponse, PaginatedResponse } from '../types/api';
import type {
  ExamListFilters,
  ExamListRecord,
  ExamMarkingOption,
  ExamTrendPoint,
  LessonPlanQueueFilters,
  MarksListFilters,
  MarkListRecord,
  MarksMatrixContext,
  MarksMatrixExam,
  MarksMatrixExistingMark,
  MarksMatrixFilters,
  MarksMatrixStudent,
} from '../types/academics';
import type { LessonPlanRecord } from '../types/approval';
import { apiClient } from './client';

/**
 * Academics workspace APIs (Sprint 7) — reuses existing Laravel Sanctum routes.
 */
export const academicsWorkspaceApi = {
  listExams(
    params?: ExamListFilters,
  ): Promise<ApiResponse<PaginatedResponse<ExamListRecord>>> {
    const query: Record<string, string | number> = {};
    if (params?.status) query.status = params.status;
    if (params?.academic_year_id != null) query.academic_year_id = params.academic_year_id;
    if (params?.term_id != null) query.term_id = params.term_id;
    if (params?.classroom_id != null) query.classroom_id = params.classroom_id;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<ExamListRecord>>('/exams', query);
  },

  getExam(id: number): Promise<ApiResponse<ExamListRecord>> {
    return apiClient.get<ExamListRecord>(`/exams/${id}`);
  },

  getExamMarkingOptions(examId: number): Promise<ApiResponse<ExamMarkingOption[]>> {
    return apiClient.get<ExamMarkingOption[]>(`/exams/${examId}/marking-options`);
  },

  listExamSessions(params?: {
    academic_year_id?: number;
    term_id?: number;
    classroom_id?: number;
    stream_id?: number;
  }): Promise<ApiResponse<import('../types/academics').ExamSessionRecord[]>> {
    const query: Record<string, number> = {};
    if (params?.academic_year_id != null) query.academic_year_id = params.academic_year_id;
    if (params?.term_id != null) query.term_id = params.term_id;
    if (params?.classroom_id != null) query.classroom_id = params.classroom_id;
    if (params?.stream_id != null) query.stream_id = params.stream_id;
    return apiClient.get('/exam-sessions', query);
  },

  listMarks(
    params: MarksListFilters,
  ): Promise<ApiResponse<PaginatedResponse<MarkListRecord>>> {
    return apiClient.get<PaginatedResponse<MarkListRecord>>('/marks', {
      exam_id: params.exam_id,
      subject_id: params.subject_id,
      classroom_id: params.classroom_id,
    });
  },

  getMarksMatrixContext(classroomId?: number): Promise<ApiResponse<MarksMatrixContext>> {
    const query: Record<string, number> = {};
    if (classroomId != null) query.classroom_id = classroomId;
    return apiClient.get<MarksMatrixContext>('/marks/matrix/context', query);
  },

  getMarksMatrix(params: MarksMatrixFilters): Promise<
    ApiResponse<{
      students: MarksMatrixStudent[];
      exams: MarksMatrixExam[];
      existing_marks: MarksMatrixExistingMark[];
    }>
  > {
    const query: Record<string, number> = {
      exam_type_id: params.exam_type_id,
      classroom_id: params.classroom_id,
    };
    if (params.stream_id != null) query.stream_id = params.stream_id;
    return apiClient.get('/marks/matrix', query);
  },

  getExamTrends(params: {
    academic_year_id: number;
    term_id: number;
    classroom_id?: number;
    stream_id?: number;
    subject_id?: number;
  }): Promise<ApiResponse<ExamTrendPoint[]>> {
    return apiClient.get<ExamTrendPoint[]>('/reports/exams/trends', params);
  },

  listLessonPlanReviewQueue(
    params?: LessonPlanQueueFilters,
  ): Promise<ApiResponse<PaginatedResponse<LessonPlanRecord>>> {
    const query: Record<string, string | number> = {};
    if (params?.classroom_id != null) query.classroom_id = params.classroom_id;
    if (params?.teacher_id != null) query.teacher_id = params.teacher_id;
    if (params?.date_from) query.date_from = params.date_from;
    if (params?.date_to) query.date_to = params.date_to;
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<LessonPlanRecord>>(
      '/lesson-plans/review-queue',
      query,
    );
  },

  getLessonPlan(id: number): Promise<ApiResponse<LessonPlanRecord>> {
    return apiClient.get<LessonPlanRecord>(`/lesson-plans/${id}`);
  },

  createLessonPlan(payload: {
    title: string;
    planned_date: string;
    classroom_id: number;
    subject_id: number;
    academic_year_id: number;
    term_id: number;
    timetable_id?: number;
    duration_minutes?: number;
    learning_outcomes?: string;
    introduction?: string;
    lesson_development?: string;
    assessment?: string;
    conclusion?: string;
  }): Promise<ApiResponse<LessonPlanRecord>> {
    return apiClient.post<LessonPlanRecord>('/lesson-plans', payload);
  },

  submitLessonPlan(id: number): Promise<ApiResponse<LessonPlanRecord>> {
    return apiClient.post<LessonPlanRecord>(`/lesson-plans/${id}/submit`, {});
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

  enterMarks(data: {
    exam_id: number;
    subject_id: number;
    classroom_id: number;
    marks: { student_id: number; marks: number; remarks?: string }[];
  }): Promise<ApiResponse<{ count: number; message: string }>> {
    return apiClient.post('/exam-marks/batch', data);
  },

  enterMarksMatrix(data: {
    exam_type_id: number;
    classroom_id: number;
    stream_id?: number;
    entries: { student_id: number; exam_id: number; marks?: number; remarks?: string }[];
  }): Promise<ApiResponse<{ count: number; skipped: number; message: string }>> {
    return apiClient.post('/exam-marks/matrix/batch', data);
  },
};
