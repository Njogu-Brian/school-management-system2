import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface AppClientIssueRow {
  id: number;
  user_id: number | null;
  user_name?: string | null;
  user_email?: string | null;
  app: string;
  platform: string | null;
  app_version: string | null;
  role: string | null;
  message: string;
  stack: string | null;
  component_stack: string | null;
  extra: Record<string, unknown> | null;
  created_at: string | null;
}

export interface AppClientIssuesPayload {
  items: AppClientIssueRow[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export const appIssuesApi = {
  report(payload: {
    app?: 'users' | 'admin';
    platform?: string;
    app_version?: string;
    role?: string;
    message: string;
    stack?: string;
    component_stack?: string;
    extra?: Record<string, unknown>;
  }): Promise<ApiResponse<{ id: number }>> {
    return apiClient.post('/app-issues', payload);
  },

  list(params?: {
    app?: 'users' | 'admin';
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<AppClientIssuesPayload>> {
    return apiClient.get<AppClientIssuesPayload>('/admin/app-issues', params);
  },
};
