import { apiClient } from './client';
import {
    Announcement,
    Message,
    MessageTemplate,
    Notification,
    CommunicationFilters,
} from '../types/communication.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const communicationApi = {
    // ========== Announcements ==========
    async getAnnouncements(filters?: CommunicationFilters): Promise<ApiResponse<PaginatedResponse<Announcement>>> {
        return apiClient.get<PaginatedResponse<Announcement>>('/announcements', filters);
    },

    async getAnnouncement(id: number): Promise<ApiResponse<Announcement>> {
        return apiClient.get<Announcement>(`/announcements/${id}`);
    },

    async createAnnouncement(data: any): Promise<ApiResponse<Announcement>> {
        return apiClient.post<Announcement>('/announcements', data);
    },

    async updateAnnouncement(id: number, data: any): Promise<ApiResponse<Announcement>> {
        return apiClient.put<Announcement>(`/announcements/${id}`, data);
    },

    async publishAnnouncement(id: number): Promise<ApiResponse<Announcement>> {
        return apiClient.post<Announcement>(`/announcements/${id}/publish`);
    },

    async deleteAnnouncement(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/announcements/${id}`);
    },

    // ========== Messages (Email/SMS) ==========
    async getMessages(filters?: CommunicationFilters): Promise<ApiResponse<PaginatedResponse<Message>>> {
        return apiClient.get<PaginatedResponse<Message>>('/messages', filters);
    },

    async getMessage(id: number): Promise<ApiResponse<Message>> {
        return apiClient.get<Message>(`/messages/${id}`);
    },

    async sendMessage(data: {
        type: 'email' | 'sms';
        subject?: string;
        body: string;
        recipient_type: string;
        recipients?: string[];
        target_classes?: number[];
    }): Promise<ApiResponse<Message>> {
        return apiClient.post<Message>('/messages/send', data);
    },

    async getMessageDeliveryStatus(id: number): Promise<ApiResponse<any>> {
        return apiClient.get(`/messages/${id}/delivery-status`);
    },

    // ========== Message Templates ==========
    async getTemplates(filters?: { type?: string; category?: string }): Promise<ApiResponse<MessageTemplate[]>> {
        return apiClient.get<MessageTemplate[]>('/message-templates', filters);
    },

    async getTemplate(id: number): Promise<ApiResponse<MessageTemplate>> {
        return apiClient.get<MessageTemplate>(`/message-templates/${id}`);
    },

    async createTemplate(data: any): Promise<ApiResponse<MessageTemplate>> {
        return apiClient.post<MessageTemplate>('/message-templates', data);
    },

    async updateTemplate(id: number, data: any): Promise<ApiResponse<MessageTemplate>> {
        return apiClient.put<MessageTemplate>(`/message-templates/${id}`, data);
    },

    async deleteTemplate(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/message-templates/${id}`);
    },

    // ========== Notifications ==========
    async getNotifications(filters?: { is_read?: boolean; category?: string }): Promise<ApiResponse<PaginatedResponse<Notification>>> {
        return apiClient.get<PaginatedResponse<Notification>>('/notifications', filters);
    },

    async markAsRead(id: number): Promise<ApiResponse<Notification>> {
        return apiClient.post<Notification>(`/notifications/${id}/read`);
    },

    async markAllAsRead(): Promise<ApiResponse<{ count: number }>> {
        return apiClient.post('/notifications/mark-all-read');
    },

    async deleteNotification(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/notifications/${id}`);
    },

    // ========== Push Notifications ==========
    async registerDeviceToken(token: string, platform: string): Promise<ApiResponse<{ message: string }>> {
        return apiClient.post('/push-notifications/register', { token, platform });
    },

    async updateNotificationSettings(settings: any): Promise<ApiResponse<any>> {
        return apiClient.put('/push-notifications/settings', settings);
    },
};
