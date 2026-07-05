import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export const accountApi = {
  changePassword(payload: {
    current_password: string;
    new_password: string;
    new_password_confirmation: string;
  }): Promise<ApiResponse<null>> {
    return apiClient.post<null>('/password/change', payload);
  },
};
