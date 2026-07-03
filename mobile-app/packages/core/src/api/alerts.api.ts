import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface SystemAlertRecord {
  id: string;
  title: string;
  body: string;
  category: string;
  severity: string;
  deep_link?: string | null;
  requires_action: boolean;
  is_acknowledged: boolean;
  is_read: boolean;
  created_at: string;
  metadata?: Record<string, unknown>;
}

export interface SystemAlertsPayload {
  alerts: SystemAlertRecord[];
  pending_count: number;
}

export const alertsApi = {
  list(): Promise<ApiResponse<SystemAlertsPayload>> {
    return apiClient.get<SystemAlertsPayload>('/admin-alerts');
  },

  acknowledge(id: string): Promise<ApiResponse<{ pending_count: number }>> {
    return apiClient.post<{ pending_count: number }>(`/admin-alerts/${id}/acknowledge`);
  },
};
