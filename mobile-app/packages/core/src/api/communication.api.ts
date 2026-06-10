import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface AnnouncementRecord {
  id: number;
  title: string;
  content: string;
  active?: boolean;
  expires_at?: string | null;
  created_at: string;
  updated_at?: string | null;
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
  delivered_at?: string | null;
  created_at?: string | null;
}

export interface CommunicationTemplateDetail extends CommunicationTemplate {
  created_at?: string | null;
  updated_at?: string | null;
}

export interface SmsRecipient {
  phone: string;
  name?: string | null;
  relation?: string | null;
  student_name?: string | null;
  classroom?: string | null;
}

export interface SmsRecipientsResult {
  recipients: SmsRecipient[];
  total: number;
  students_matched: number;
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

  getTemplate(id: number): Promise<ApiResponse<CommunicationTemplateDetail>> {
    return apiClient.get<CommunicationTemplateDetail>(`/communication/templates/${id}`);
  },

  createTemplate(payload: {
    title: string;
    type: string;
    code?: string | null;
    subject?: string | null;
    content: string;
  }): Promise<ApiResponse<CommunicationTemplateDetail>> {
    return apiClient.post<CommunicationTemplateDetail>('/communication/templates', payload);
  },

  updateTemplate(
    id: number,
    payload: { title: string; type: string; code?: string | null; subject?: string | null; content: string },
  ): Promise<ApiResponse<CommunicationTemplateDetail>> {
    return apiClient.put<CommunicationTemplateDetail>(`/communication/templates/${id}`, payload);
  },

  deleteTemplate(id: number): Promise<ApiResponse<null>> {
    return apiClient.delete(`/communication/templates/${id}`);
  },

  getLog(id: number): Promise<ApiResponse<CommunicationLogRecord>> {
    return apiClient.get<CommunicationLogRecord>(`/communication/logs/${id}`);
  },

  listRecipients(params?: { classroom_id?: number }): Promise<ApiResponse<SmsRecipientsResult>> {
    return apiClient.get<SmsRecipientsResult>('/communication/recipients', params);
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

  sendWhatsApp(payload: {
    message?: string;
    template_id?: number;
    custom_numbers?: string;
    phones?: string[];
  }): Promise<ApiResponse<{ sent: number; failed: number; total: number }>> {
    return apiClient.post('/communication/whatsapp', payload);
  },
};
