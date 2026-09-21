import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export const accountApi = {
  changePassword(payload: {
    current_password?: string;
    new_password: string;
    new_password_confirmation: string;
  }): Promise<ApiResponse<null>> {
    return apiClient.post<null>('/password/change', payload);
  },

  setUnlockPin(payload: { pin: string; pin_confirmation: string }): Promise<ApiResponse<null>> {
    return apiClient.put<null>('/account/unlock-pin', payload);
  },

  clearUnlockPin(): Promise<ApiResponse<null>> {
    return apiClient.delete<null>('/account/unlock-pin');
  },

  registerBiometricUnlock(payload: {
    selector: string;
    secret: string;
    device_name?: string;
  }): Promise<ApiResponse<null>> {
    return apiClient.put<null>('/account/biometric-unlock', payload);
  },

  revokeBiometricUnlock(selector?: string): Promise<ApiResponse<null>> {
    return apiClient.delete<null>(
      selector
        ? `/account/biometric-unlock?selector=${encodeURIComponent(selector)}`
        : '/account/biometric-unlock',
    );
  },
};
