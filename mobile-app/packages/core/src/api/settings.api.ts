import type { ApiResponse } from '../types/api';
import type {
  AcademicYearRecord,
  GradingSettingsRecord,
  RoleSettingsRecord,
  SchoolSettingsRecord,
  SettingsClassroomRecord,
  SettingsStreamRecord,
  SettingsSubjectRecord,
  TermRecord,
} from '../types/settings';
import { apiClient } from './client';

/**
 * Settings Hub read APIs (Sprint 4 Batch 1).
 */
export const settingsApi = {
  getSchool(): Promise<ApiResponse<SchoolSettingsRecord>> {
    return apiClient.get<SchoolSettingsRecord>('/settings/school');
  },

  getAcademicYears(): Promise<ApiResponse<AcademicYearRecord[]>> {
    return apiClient.get<AcademicYearRecord[]>('/settings/academic-years');
  },

  getTerms(academicYearId?: number): Promise<ApiResponse<TermRecord[]>> {
    const params =
      academicYearId != null ? { academic_year_id: academicYearId } : undefined;
    return apiClient.get<TermRecord[]>('/settings/terms', params);
  },

  getClasses(): Promise<ApiResponse<SettingsClassroomRecord[]>> {
    return apiClient.get<SettingsClassroomRecord[]>('/settings/classes');
  },

  getStreams(classId: number): Promise<ApiResponse<SettingsStreamRecord[]>> {
    return apiClient.get<SettingsStreamRecord[]>(`/settings/classes/${classId}/streams`);
  },

  getSubjects(): Promise<ApiResponse<SettingsSubjectRecord[]>> {
    return apiClient.get<SettingsSubjectRecord[]>('/settings/subjects');
  },

  getGrading(): Promise<ApiResponse<GradingSettingsRecord>> {
    return apiClient.get<GradingSettingsRecord>('/settings/grading');
  },

  getRoles(): Promise<ApiResponse<RoleSettingsRecord[]>> {
    return apiClient.get<RoleSettingsRecord[]>('/settings/roles');
  },
};
