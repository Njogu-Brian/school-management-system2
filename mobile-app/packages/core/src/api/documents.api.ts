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

  /** Parent/staff: upload passport photo or birth certificate. */
  uploadStudentDocument(
    studentId: number,
    file: { uri: string; name: string; type: string },
    category: 'student_profile_photo' | 'student_birth_certificate',
    title?: string,
  ): Promise<ApiResponse<{ id: number; category: string; file_name: string }>> {
    const form = new FormData();
    form.append('file', file as unknown as Blob);
    form.append('category', category);
    if (title) form.append('title', title);
    return apiClient.postMultipart(`/students/${studentId}/documents`, form);
  },

  /** Parent: upload one ID card for linked parent_info. */
  uploadParentIdCard(
    file: { uri: string; name: string; type: string },
    slot?: 'father' | 'mother' | 'guardian',
  ): Promise<ApiResponse<{ id: number; category: string; slot: string }>> {
    const form = new FormData();
    form.append('file', file as unknown as Blob);
    if (slot) form.append('slot', slot);
    return apiClient.postMultipart('/parent/documents/id-card', form);
  },
};
