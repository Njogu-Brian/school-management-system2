import { apiClient } from './client';
import type { ApiResponse } from '../types/api';

export const deviceApi = {
  registerPushToken(token: string, platform?: string): Promise<ApiResponse<{ message?: string }>> {
    return apiClient.post<{ message?: string }>('/device-tokens', { token, platform });
  },

  revokePushToken(token: string): Promise<ApiResponse<{ message?: string }>> {
    return apiClient.post<{ message?: string }>('/device-tokens/revoke', { token });
  },
};
