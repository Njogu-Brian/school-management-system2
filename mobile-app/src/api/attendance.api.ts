import { apiClient } from './client';
import {
    AttendanceRecord,
    MarkAttendanceData,
    AttendanceFilters,
    AttendanceStats,
    AttendanceAnalytics,
} from '@types/attendance.types';
import { ApiResponse, PaginatedResponse } from '@types/api.types';

export const attendanceApi = {
    // Get attendance records with filters
    async getAttendanceRecords(filters?: AttendanceFilters): Promise<ApiResponse<PaginatedResponse<AttendanceRecord>>> {
        return apiClient.get<PaginatedResponse<AttendanceRecord>>('/attendance', filters);
    },

    // Mark attendance for a class
    async markAttendance(data: MarkAttendanceData): Promise<ApiResponse<{ message: string; count: number }>> {
        return apiClient.post('/attendance/mark', data);
    },

    // Get attendance for specific date and class
    async getClassAttendance(date: string, classId: number, streamId?: number): Promise<ApiResponse<AttendanceRecord[]>> {
        return apiClient.get<AttendanceRecord[]>('/attendance/class', {
            date,
            class_id: classId,
            stream_id: streamId,
        });
    },

    // Get student attendance stats
    async getStudentStats(studentId: number, dateFrom?: string, dateTo?: string): Promise<ApiResponse<AttendanceStats>> {
        return apiClient.get<AttendanceStats>(`/attendance/student/${studentId}/stats`, {
            date_from: dateFrom,
            date_to: dateTo,
        });
    },

    // Get attendance analytics (at-risk students, consecutive absences)
    async getAnalytics(classId?: number, dateFrom?: string, dateTo?: string): Promise<ApiResponse<AttendanceAnalytics>> {
        return apiClient.get<AttendanceAnalytics>('/attendance/analytics', {
            class_id: classId,
            date_from: dateFrom,
            date_to: dateTo,
        });
    },

    // Update attendance record
    async updateAttendance(id: number, status: string, reason?: string): Promise<ApiResponse<AttendanceRecord>> {
        return apiClient.put<AttendanceRecord>(`/attendance/${id}`, { status, reason });
    },

    // Delete attendance record
    async deleteAttendance(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/attendance/${id}`);
    },
};
