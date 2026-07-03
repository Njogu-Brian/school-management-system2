import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface SessionRecord {
  id: number;
  name: string;
  device: string;
  platform: string;
  is_current: boolean;
  login_date: string;
  last_activity: string;
  expires_at?: string | null;
}

export interface RefreshTokenData {
  token: string;
  expires_at: string;
}

export const sessionsApi = {
  list(): Promise<ApiResponse<SessionRecord[]>> {
    return apiClient.get<SessionRecord[]>('/sessions');
  },

  revoke(tokenId: number): Promise<ApiResponse<{ revoked: number | string }>> {
    return apiClient.post<{ revoked: number | string }>('/sessions/revoke', { token_id: tokenId });
  },

  revokeOthers(): Promise<ApiResponse<{ revoked: string }>> {
    return apiClient.post<{ revoked: string }>('/sessions/revoke', { revoke_all: true });
  },

  refresh(): Promise<ApiResponse<RefreshTokenData>> {
    return apiClient.post<RefreshTokenData>('/auth/refresh');
  },

  refreshWithToken(token: string): Promise<ApiResponse<RefreshTokenData>> {
    return apiClient.postWithToken<RefreshTokenData>('/auth/refresh', token);
  },
};
