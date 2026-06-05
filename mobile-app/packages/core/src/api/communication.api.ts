import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export interface AnnouncementRecord {
  id: number;
  title: string;
  content: string;
  expires_at?: string | null;
  created_at: string;
}

export const communicationApi = {
  listAnnouncements(
    params?: { page?: number; per_page?: number; status?: string },
  ): Promise<ApiResponse<PaginatedResponse<AnnouncementRecord>>> {
    return apiClient.get<PaginatedResponse<AnnouncementRecord>>('/announcements', params);
  },
};
