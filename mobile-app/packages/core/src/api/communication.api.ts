import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface AnnouncementRecord {
  id: number;
  title: string;
  content: string;
  expires_at?: string | null;
  created_at: string;
}

export interface CommunicationTemplate {
  id: number;
  code?: string | null;
  title: string;
  type: string;
  subject?: string | null;
  content?: string | null;
}

export interface CommunicationLogRecord {
  id: number;
  channel?: string | null;
  contact?: string | null;
  title?: string | null;
  message?: string | null;
  status?: string | null;
  sent_at?: string | null;
  created_at?: string | null;
}

export const communicationApi = {
  listAnnouncements(
    params?: { page?: number; per_page?: number; status?: string },
  ): Promise<ApiResponse<PaginatedResponse<AnnouncementRecord>>> {
    return apiClient.get<PaginatedResponse<AnnouncementRecord>>('/announcements', params);
  },

  getAnnouncement(id: number): Promise<ApiResponse<AnnouncementRecord>> {
    return apiClient.get<AnnouncementRecord>(`/announcements/${id}`);
  },

  createAnnouncement(payload: {
    title: string;
    content: string;
    active: boolean;
    expires_at?: string | null;
  }): Promise<ApiResponse<AnnouncementRecord>> {
    return apiClient.post<AnnouncementRecord>('/announcements', payload);
  },

  updateAnnouncement(
    id: number,
    payload: { title: string; content: string; active: boolean; expires_at?: string | null },
  ): Promise<ApiResponse<AnnouncementRecord>> {
    return apiClient.put<AnnouncementRecord>(`/announcements/${id}`, payload);
  },

  deleteAnnouncement(id: number): Promise<ApiResponse<null>> {
    return apiClient.delete(`/announcements/${id}`);
  },

  listTemplates(params?: { type?: string }): Promise<ApiResponse<CommunicationTemplate[]>> {
    return apiClient.get<CommunicationTemplate[]>('/communication/templates', params);
  },

  listLogs(params?: {
    page?: number;
    per_page?: number;
    channel?: string;
    status?: string;
  }): Promise<ApiResponse<PaginatedResponse<CommunicationLogRecord>>> {
    return apiClient.get<PaginatedResponse<CommunicationLogRecord>>('/communication/logs', params);
  },

  sendSms(payload: {
    message?: string;
    template_id?: number;
    custom_numbers?: string;
    phones?: string[];
    sender_id?: 'finance' | 'default';
  }): Promise<ApiResponse<{ sent: number; failed: number; total: number }>> {
    return apiClient.post('/communication/sms', payload);
  },
};
