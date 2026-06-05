import type { ApiResponse } from '../types/api';
import type { AppBranding } from '../types/branding';
import { apiClient } from './client';

export const brandingApi = {
  getAppBranding(): Promise<ApiResponse<AppBranding>> {
    return apiClient.get<AppBranding>('/app-branding');
  },
};
