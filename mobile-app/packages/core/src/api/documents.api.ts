import type { ApiResponse, PaginatedResponse } from '../types/api';
import type { DocumentListRecord } from '../types/documents';
import { apiClient } from './client';

export const documentsApi = {
  listStudentDocuments(
    studentId: number,
    params?: { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<DocumentListRecord> & { student_id: number }>> {
    return apiClient.get(`/students/${studentId}/documents`, params);
  },

  listStaffDocuments(
    staffId: number,
    params?: { page?: number; per_page?: number },
  ): Promise<ApiResponse<PaginatedResponse<DocumentListRecord> & { staff_id: number }>> {
    return apiClient.get(`/staff/${staffId}/documents`, params);
  },

  uploadStaffDocument(
    staffId: number,
    formData: FormData,
  ): Promise<ApiResponse<DocumentListRecord>> {
    return apiClient.postMultipart<DocumentListRecord>(`/staff/${staffId}/documents`, formData);
  },
};
