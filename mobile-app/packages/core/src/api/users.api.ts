import type { ApiResponse, PaginatedResponse } from '../types/api';
import { apiClient } from './client';

export type PasswordChangeTarget = {
  id: number;
  name: string;
  login: string | null;
  email: string | null;
  phone: string | null;
  groups: string[];
  must_change_password: boolean;
};

export const usersApi = {
  passwordChangeTargets(params?: {
    group?: 'staff' | 'parents' | 'all';
    q?: string;
    page?: number;
  }): Promise<ApiResponse<PaginatedResponse<PasswordChangeTarget>>> {
    return apiClient.get('/users/password-change-targets', params);
  },

  requirePasswordChange(payload: {
    group: 'staff' | 'parents' | 'all';
    all?: boolean;
    user_ids?: number[];
    q?: string;
  }): Promise<ApiResponse<{ count: number }>> {
    return apiClient.post('/users/require-password-change', payload);
  },
};
