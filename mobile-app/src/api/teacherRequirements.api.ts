import { apiClient } from './client';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export interface RequirementStudent {
    id: number;
    admission_number: string;
    full_name: string;
    class_name?: string | null;
    stream_name?: string | null;
    avatar?: string | null;
    is_new_joiner: boolean;
    can_teacher_receive: boolean;
}

export interface RequirementItem {
    template_id: number;
    requirement_id: number | null;
    name: string;
    brand?: string | null;
    unit?: string | null;
    quantity_required: number;
    quantity_collected: number;
    status: 'pending' | 'partial' | 'complete';
    student_type: 'new' | 'existing' | 'both';
    custody_type?: string | null;
    notes?: string | null;
}

export interface RequirementStudentDetail {
    student: {
        id: number;
        full_name: string;
        admission_number: string;
        class_name?: string | null;
        is_new_joiner: boolean;
    };
    current_term: { id: number; name: string } | null;
    items: RequirementItem[];
}

export const teacherRequirementsApi = {
    async getStudents(params?: {
        classroom_id?: number;
        search?: string;
        per_page?: number;
        page?: number;
    }): Promise<ApiResponse<PaginatedResponse<RequirementStudent>>> {
        return apiClient.get('/teacher/requirements/students', params);
    },

    async getStudentTemplates(studentId: number): Promise<ApiResponse<RequirementStudentDetail>> {
        return apiClient.get(`/teacher/requirements/students/${studentId}/templates`);
    },

    async collect(payload: {
        student_id: number;
        template_id: number;
        quantity_received: number;
        notes?: string;
    }): Promise<ApiResponse<{ id: number; quantity_collected: number; status: string }>> {
        return apiClient.post('/teacher/requirements/collect', payload);
    },
};
