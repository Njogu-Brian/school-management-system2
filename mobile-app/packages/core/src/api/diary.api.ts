import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface DiaryThreadSummary {
  id: number;
  student_id: number;
  student_name?: string | null;
  admission_number?: string | null;
  class_name?: string | null;
  unread_count?: number;
  latest_entry?: {
    id: number;
    content: string;
    author_type?: string;
    author_name?: string | null;
    created_at?: string | null;
  } | null;
  updated_at?: string | null;
}

export interface DiaryEntryRecord {
  id: number;
  content: string;
  author_id: number;
  author_type: string;
  author_name?: string | null;
  parent_entry_id?: number | null;
  attachments?: string[] | null;
  attachment_urls?: string[];
  is_read: boolean;
  is_mine: boolean;
  created_at?: string | null;
}

export interface DiaryThreadDetail {
  id: number;
  student_id: number;
  student_name?: string | null;
  class_name?: string | null;
  entries: DiaryEntryRecord[];
}

export const diaryApi = {
  list(params?: {
    student_id?: number;
    search?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<PaginatedResponse<DiaryThreadSummary>>> {
    return apiClient.get('/diaries', params);
  },

  getForStudent(studentId: number): Promise<ApiResponse<DiaryThreadDetail>> {
    return apiClient.get(`/diaries/students/${studentId}`);
  },

  sendMessage(
    studentId: number,
    payload: { content: string; parent_entry_id?: number },
    attachments?: { uri: string; name: string; type: string }[],
  ): Promise<ApiResponse<DiaryEntryRecord>> {
    if (attachments && attachments.length > 0) {
      const form = new FormData();
      form.append('content', payload.content);
      if (payload.parent_entry_id != null) {
        form.append('parent_entry_id', String(payload.parent_entry_id));
      }
      attachments.forEach((file, index) => {
        form.append('attachments[]', {
          uri: file.uri,
          name: file.name || `attachment-${index}`,
          type: file.type || 'application/octet-stream',
        } as unknown as Blob);
      });
      return apiClient.postMultipart(`/diaries/students/${studentId}/entries`, form);
    }
    return apiClient.post(`/diaries/students/${studentId}/entries`, payload);
  },
};
