import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface NotificationRecord {
  id: string;
  user_id?: number;
  title: string;
  body: string;
  type?: string;
  category: string;
  source_module?: string;
  deep_link?: string | null;
  data?: Record<string, unknown>;
  is_read: boolean;
  created_at: string;
  read_at?: string | null;
}

export const notificationsApi = {
  list(params?: {
    page?: number;
    per_page?: number;
    is_read?: boolean;
    category?: string;
    search?: string;
  }): Promise<ApiResponse<PaginatedResponse<NotificationRecord>>> {
    return apiClient.get<PaginatedResponse<NotificationRecord>>('/notifications', params);
  },

  unreadCount(): Promise<ApiResponse<{ count: number }>> {
    return apiClient.get<{ count: number }>('/notifications/unread-count');
  },

  markRead(id: string): Promise<ApiResponse<NotificationRecord>> {
    return apiClient.post<NotificationRecord>(`/notifications/${id}/read`);
  },

  markAllRead(): Promise<ApiResponse<{ count: number }>> {
    return apiClient.post<{ count: number }>('/notifications/mark-all-read');
  },

  delete(id: string): Promise<ApiResponse<null>> {
    return apiClient.delete(`/notifications/${id}`);
  },
};
