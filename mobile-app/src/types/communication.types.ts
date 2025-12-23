export interface Announcement {
    id: number;
    title: string;
    content: string;
    type: 'general' | 'urgent' | 'event' | 'academic' | 'holiday';
    priority: 'low' | 'medium' | 'high';
    target_audience: 'all' | 'students' | 'staff' | 'parents' | 'specific';
    target_classes?: number[];
    published_by: number;
    published_by_name?: string;
    publish_date: string;
    expiry_date?: string;
    status: 'draft' | 'published' | 'expired';
    attachments?: string[];
    view_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Message {
    id: number;
    subject: string;
    body: string;
    type: 'email' | 'sms';
    sender_id: number;
    sender_name?: string;
    recipient_type: 'individual' | 'group' | 'class' | 'all';
    recipients?: string[];
    recipient_count?: number;
    status: 'draft' | 'sending' | 'sent' | 'failed';
    sent_at?: string;
    delivery_status?: {
        sent: number;
        delivered: number;
        failed: number;
    };
    created_at: string;
    updated_at: string;
}

export interface MessageTemplate {
    id: number;
    name: string;
    type: 'email' | 'sms';
    subject?: string;
    body: string;
    variables?: string[];
    category: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Notification {
    id: number;
    user_id: number;
    title: string;
    body: string;
    type: 'info' | 'success' | 'warning' | 'error';
    category: 'announcement' | 'fee' | 'attendance' | 'exam' | 'general';
    data?: any;
    is_read: boolean;
    created_at: string;
    read_at?: string;
}

export interface CommunicationFilters {
    search?: string;
    type?: string;
    status?: string;
    target_audience?: string;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
}
