import { apiClient } from './client';
import type { AppBranding } from '@types/branding.types';

export const brandingApi = {
    /** Public: school name and logo URL from portal settings (no auth). */
    async getBranding(): Promise<AppBranding> {
        const data = await apiClient.get<AppBranding>('/app-branding');
        return data as unknown as AppBranding;
    },
};
