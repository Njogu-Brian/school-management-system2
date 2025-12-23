import { apiClient } from './client';
import {
    Student,
    StudentFilters,
    CreateStudentData,
    UpdateStudentData,
    Class,
    Stream,
} from '@types/student.types';
import { ApiResponse, PaginatedResponse } from '@types/api.types';

export const studentsApi = {
    // Get students list with filters
    async getStudents(filters?: StudentFilters): Promise<ApiResponse<PaginatedResponse<Student>>> {
        return apiClient.get<PaginatedResponse<Student>>('/students', filters);
    },

    // Get single student details
    async getStudent(id: number): Promise<ApiResponse<Student>> {
        return apiClient.get<Student>(`/students/${id}`);
    },

    // Create new student
    async createStudent(data: CreateStudentData): Promise<ApiResponse<Student>> {
        return apiClient.post<Student>('/students', data);
    },

    // Update student
    async updateStudent(id: number, data: UpdateStudentData): Promise<ApiResponse<Student>> {
        return apiClient.put<Student>(`/students/${id}`, data);
    },

    // Delete/Archive student
    async archiveStudent(id: number): Promise<ApiResponse<void>> {
        return apiClient.post<void>(`/students/${id}/archive`);
    },

    // Restore archived student
    async restoreStudent(id: number): Promise<ApiResponse<void>> {
        return apiClient.post<void>(`/students/${id}/restore`);
    },

    // Get classes list
    async getClasses(): Promise<ApiResponse<Class[]>> {
        return apiClient.get<Class[]>('/classes');
    },

    // Get streams for a class
    async getStreams(classId: number): Promise<ApiResponse<Stream[]>> {
        return apiClient.get<Stream[]>(`/classes/${classId}/streams`);
    },

    // Bulk upload students
    async bulkUpload(file: FormData): Promise<ApiResponse<{ success: number; errors: any[] }>> {
        return apiClient.upload('/students/bulk-upload', file);
    },

    // Download bulk upload template
    async downloadTemplate(): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>('/students/bulk-upload-template');
    },

    // Get student stats (for detail screen)
    async getStudentStats(id: number): Promise<ApiResponse<{
        attendance_percentage: number;
        fees_balance: number;
        exam_average: number;
    }>> {
        return apiClient.get(`/students/${id}/stats`);
    },
};
