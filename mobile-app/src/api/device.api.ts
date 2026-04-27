import { apiClient } from './client';
import { ApiResponse } from 'types/api.types';

export const deviceApi = {
    async registerPushToken(token: string, platform?: string): Promise<ApiResponse<{ message?: string }>> {
        return apiClient.post('/device-tokens', { token, platform });
    },

    async revokePushToken(token: string): Promise<ApiResponse<{ message?: string }>> {
        return apiClient.post('/device-tokens/revoke', { token });
    },
};
