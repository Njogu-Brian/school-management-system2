import type { ApiResponse, PaginatedResponse } from '../types/api';
import type {
  AcademicSummaryRecord,
  AssessmentHistoryFilters,
  AssessmentHistoryMeta,
  AssessmentHistoryRecord,
  ReportCardDetailRecord,
  ReportCardListRecord,
} from '../types/studentAcademics';
import { apiClient } from './client';

/**
 * Student academics read APIs (Phase 0 facade + existing report cards).
 */
export const academicsApi = {
  getAcademicSummary(
    studentId: number,
    params?: { academic_year_id?: number; term_id?: number },
  ): Promise<ApiResponse<AcademicSummaryRecord>> {
    return apiClient.get<AcademicSummaryRecord>(
      `/students/${studentId}/academic-summary`,
      params,
    );
  },

  getAssessmentHistory(
    studentId: number,
    params?: AssessmentHistoryFilters,
  ): Promise<
    ApiResponse<PaginatedResponse<AssessmentHistoryRecord>> & {
      meta?: AssessmentHistoryMeta;
    }
  > {
    const query: Record<string, string | number> = {};
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    if (params?.academic_year_id != null) query.academic_year_id = params.academic_year_id;
    if (params?.term_id != null) query.term_id = params.term_id;
    if (params?.subject_id != null) query.subject_id = params.subject_id;
    if (params?.type) query.type = params.type;

    return apiClient.get<PaginatedResponse<AssessmentHistoryRecord>>(
      `/students/${studentId}/assessment-history`,
      query,
    );
  },

  getReportCards(
    studentId: number,
    params?: { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<ReportCardListRecord>>> {
    const query: Record<string, string | number> = { student_id: studentId };
    if (params?.page != null) query.page = params.page;
    if (params?.per_page != null) query.per_page = params.per_page;
    return apiClient.get<PaginatedResponse<ReportCardListRecord>>('/report-cards', query);
  },

  getReportCard(id: number): Promise<ApiResponse<ReportCardDetailRecord>> {
    return apiClient.get<ReportCardDetailRecord>(`/report-cards/${id}`);
  },
};

