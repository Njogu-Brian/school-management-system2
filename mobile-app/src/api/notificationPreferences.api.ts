import { apiClient } from './client';
import { ApiResponse } from 'types/api.types';

export interface NotificationPreferences {
    push_enabled: boolean;
    email_enabled: boolean;
    sms_enabled: boolean;
    attendance_alerts: boolean;
    fee_reminders: boolean;
    announcements: boolean;
}

export const DEFAULT_NOTIFICATION_PREFERENCES: NotificationPreferences = {
    push_enabled: true,
    email_enabled: true,
    sms_enabled: false,
    attendance_alerts: true,
    fee_reminders: true,
    announcements: true,
};

export const notificationPreferencesApi = {
    async getPreferences(): Promise<ApiResponse<NotificationPreferences>> {
        return apiClient.get<NotificationPreferences>('/notification-preferences');
    },

    async updatePreferences(prefs: NotificationPreferences): Promise<ApiResponse<NotificationPreferences>> {
        return apiClient.put<NotificationPreferences>('/notification-preferences', prefs);
    },
};
