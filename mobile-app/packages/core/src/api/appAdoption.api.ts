import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export type AppAdoptionAudience = 'staff' | 'parents';
export type AppAdoptionStatus = 'all' | 'never' | 'used' | 'active';

export interface AppAdoptionRow {
  user_id: number;
  name: string;
  email: string | null;
  phone: string | null;
  roles: string[];
  parent_id: number | null;
  staff_id: number | null;
  employee_number: string | null;
  last_login_at: string | null;
  last_seen_at: string | null;
  has_active_token: boolean;
  audience: string;
}

export interface AppAdoptionPayload {
  items: AppAdoptionRow[];
  summary: { never: number; used: number; active: number; total: number };
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export const appAdoptionApi = {
  list(params?: {
    audience?: AppAdoptionAudience;
    status?: AppAdoptionStatus;
    days?: number;
    q?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<AppAdoptionPayload>> {
    return apiClient.get<AppAdoptionPayload>('/admin/app-adoption', params);
  },
};
