import type { ApiResponse } from '../types/api';
import { apiClient } from './client';

export interface NotificationPreferences {
  push_enabled: boolean;
  email_enabled: boolean;
  sms_enabled: boolean;
  attendance_alerts: boolean;
  fee_reminders: boolean;
  announcements: boolean;
}

export const notificationPreferencesApi = {
  get(): Promise<ApiResponse<NotificationPreferences>> {
    return apiClient.get<NotificationPreferences>('/notification-preferences');
  },

  update(prefs: NotificationPreferences): Promise<ApiResponse<NotificationPreferences>> {
    return apiClient.put<NotificationPreferences>('/notification-preferences', prefs);
  },
};
