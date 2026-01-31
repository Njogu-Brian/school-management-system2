import { apiClient } from './client';
import { ApiResponse, PaginatedResponse } from '@types/api.types';

export interface SupervisedClassroom {
    id: number;
    name: string;
    grade_level?: string;
    stream?: string;
    student_count?: number;
    teacher_name?: string;
}

export interface SupervisedStaffMember {
    id: number;
    staff_id: number;
    full_name: string;
    email?: string;
    designation?: string;
    department?: string;
    status?: string;
}

export interface FeeBalanceItem {
    student_id: number;
    student_name: string;
    admission_number?: string;
    class_name?: string;
    balance: number;
    currency?: string;
}

export const seniorTeacherApi = {
    async getSupervisedClassrooms(): Promise<ApiResponse<SupervisedClassroom[]>> {
        return apiClient.get<SupervisedClassroom[]>('/senior-teacher/supervised-classrooms');
    },

    async getSupervisedStaff(): Promise<ApiResponse<PaginatedResponse<SupervisedStaffMember>> | ApiResponse<SupervisedStaffMember[]>> {
        return apiClient.get<any>('/senior-teacher/supervised-staff');
    },

    async getFeeBalances(): Promise<ApiResponse<PaginatedResponse<FeeBalanceItem>> | ApiResponse<FeeBalanceItem[]>> {
        return apiClient.get<any>('/senior-teacher/fee-balances');
    },

    /** Students under senior teacher supervision (broader than own classes) */
    async getSupervisedStudents(params?: { page?: number; per_page?: number; class_id?: number }): Promise<ApiResponse<PaginatedResponse<any>>> {
        return apiClient.get<PaginatedResponse<any>>('/senior-teacher/students', params);
    },
};
