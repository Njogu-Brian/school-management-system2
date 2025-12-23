import { apiClient } from './client';
import {
    ExamType,
    Exam,
    ExamSchedule,
    Mark,
    ReportCard,
    Subject,
    Timetable,
    Assignment,
    AssignmentSubmission,
    LessonPlan,
    AcademicsFilters,
} from '../types/academics.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

export const academicsApi = {
    // ========== Exam Types ==========
    async getExamTypes(): Promise<ApiResponse<ExamType[]>> {
        return apiClient.get<ExamType[]>('/exam-types');
    },

    // ========== Exams ==========
    async getExams(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<Exam>>> {
        return apiClient.get<PaginatedResponse<Exam>>('/exams', filters);
    },

    async getExam(id: number): Promise<ApiResponse<Exam>> {
        return apiClient.get<Exam>(`/exams/${id}`);
    },

    async createExam(data: any): Promise<ApiResponse<Exam>> {
        return apiClient.post<Exam>('/exams', data);
    },

    async updateExam(id: number, data: any): Promise<ApiResponse<Exam>> {
        return apiClient.put<Exam>(`/exams/${id}`, data);
    },

    async publishExam(id: number): Promise<ApiResponse<Exam>> {
        return apiClient.post<Exam>(`/exams/${id}/publish`);
    },

    // ========== Exam Schedules ==========
    async getExamSchedules(examId: number): Promise<ApiResponse<ExamSchedule[]>> {
        return apiClient.get<ExamSchedule[]>(`/exams/${examId}/schedules`);
    },

    async createExamSchedule(examId: number, data: any): Promise<ApiResponse<ExamSchedule>> {
        return apiClient.post<ExamSchedule>(`/exams/${examId}/schedules`, data);
    },

    // ========== Marks ==========
    async getMarks(filters: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<Mark>>> {
        return apiClient.get<PaginatedResponse<Mark>>('/marks', filters);
    },

    async enterMarks(data: {
        exam_id: number;
        subject_id: number;
        marks: { student_id: number; marks: number; remarks?: string }[];
    }): Promise<ApiResponse<{ count: number; message: string }>> {
        return apiClient.post('/marks/batch', data);
    },

    async updateMark(id: number, data: { marks: number; remarks?: string }): Promise<ApiResponse<Mark>> {
        return apiClient.put<Mark>(`/marks/${id}`, data);
    },

    async getStudentMarks(studentId: number, filters?: AcademicsFilters): Promise<ApiResponse<Mark[]>> {
        return apiClient.get<Mark[]>(`/students/${studentId}/marks`, filters);
    },

    // ========== Report Cards ==========
    async getReportCards(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<ReportCard>>> {
        return apiClient.get<PaginatedResponse<ReportCard>>('/report-cards', filters);
    },

    async getReportCard(id: number): Promise<ApiResponse<ReportCard>> {
        return apiClient.get<ReportCard>(`/report-cards/${id}`);
    },

    async generateReportCard(data: {
        student_id: number;
        term_id: number;
        academic_year_id: number;
    }): Promise<ApiResponse<ReportCard>> {
        return apiClient.post<ReportCard>('/report-cards/generate', data);
    },

    async generateBulkReportCards(data: {
        class_id: number;
        term_id: number;
        academic_year_id: number;
    }): Promise<ApiResponse<{ count: number; message: string }>> {
        return apiClient.post('/report-cards/generate-bulk', data);
    },

    async publishReportCard(id: number): Promise<ApiResponse<ReportCard>> {
        return apiClient.post<ReportCard>(`/report-cards/${id}/publish`);
    },

    async downloadReportCard(id: number): Promise<ApiResponse<Blob>> {
        return apiClient.get<Blob>(`/report-cards/${id}/download`);
    },

    // ========== Subjects ==========
    async getSubjects(filters?: { class_id?: number; is_active?: boolean }): Promise<ApiResponse<Subject[]>> {
        return apiClient.get<Subject[]>('/subjects', filters);
    },

    async getSubject(id: number): Promise<ApiResponse<Subject>> {
        return apiClient.get<Subject>(`/subjects/${id}`);
    },

    // ========== Timetables ==========
    async getClassTimetable(classId: number, termId: number): Promise<ApiResponse<Timetable>> {
        return apiClient.get<Timetable>(`/timetables/class/${classId}`, { term_id: termId });
    },

    async getTeacherTimetable(teacherId: number, termId: number): Promise<ApiResponse<Timetable>> {
        return apiClient.get<Timetable>(`/timetables/teacher/${teacherId}`, { term_id: termId });
    },

    async getStudentTimetable(studentId: number, termId: number): Promise<ApiResponse<Timetable>> {
        return apiClient.get<Timetable>(`/timetables/student/${studentId}`, { term_id: termId });
    },

    // ========== Assignments/Homework ==========
    async getAssignments(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<Assignment>>> {
        return apiClient.get<PaginatedResponse<Assignment>>('/assignments', filters);
    },

    async getAssignment(id: number): Promise<ApiResponse<Assignment>> {
        return apiClient.get<Assignment>(`/assignments/${id}`);
    },

    async createAssignment(data: any): Promise<ApiResponse<Assignment>> {
        return apiClient.post<Assignment>('/assignments', data);
    },

    async updateAssignment(id: number, data: any): Promise<ApiResponse<Assignment>> {
        return apiClient.put<Assignment>(`/assignments/${id}`, data);
    },

    async deleteAssignment(id: number): Promise<ApiResponse<void>> {
        return apiClient.delete<void>(`/assignments/${id}`);
    },

    async getAssignmentSubmissions(assignmentId: number): Promise<ApiResponse<AssignmentSubmission[]>> {
        return apiClient.get<AssignmentSubmission[]>(`/assignments/${assignmentId}/submissions`);
    },

    async submitAssignment(assignmentId: number, formData: FormData): Promise<ApiResponse<AssignmentSubmission>> {
        return apiClient.upload(`/assignments/${assignmentId}/submit`, formData);
    },

    async gradeSubmission(
        submissionId: number,
        data: { grade: number; feedback?: string }
    ): Promise<ApiResponse<AssignmentSubmission>> {
        return apiClient.put<AssignmentSubmission>(`/assignment-submissions/${submissionId}/grade`, data);
    },

    // ========== Lesson Plans ==========
    async getLessonPlans(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<LessonPlan>>> {
        return apiClient.get<PaginatedResponse<LessonPlan>>('/lesson-plans', filters);
    },

    async getLessonPlan(id: number): Promise<ApiResponse<LessonPlan>> {
        return apiClient.get<LessonPlan>(`/lesson-plans/${id}`);
    },

    async createLessonPlan(data: any): Promise<ApiResponse<LessonPlan>> {
        return apiClient.post<LessonPlan>('/lesson-plans', data);
    },

    async updateLessonPlan(id: number, data: any): Promise<ApiResponse<LessonPlan>> {
        return apiClient.put<LessonPlan>(`/lesson-plans/${id}`, data);
    },

    async approveLessonPlan(id: number): Promise<ApiResponse<LessonPlan>> {
        return apiClient.post<LessonPlan>(`/lesson-plans/${id}/approve`);
    },

    // ========== Analytics ==========
    async getExamAnalytics(examId: number): Promise<ApiResponse<{
        total_students: number;
        entered_marks: number;
        pending_marks: number;
        average_score: number;
        highest_score: number;
        lowest_score: number;
        pass_rate: number;
    }>> {
        return apiClient.get(`/exams/${examId}/analytics`);
    },

    async getStudentPerformance(studentId: number, filters?: AcademicsFilters): Promise<ApiResponse<{
        average_score: number;
        total_exams: number;
        subjects_performance: any[];
        trend: any[];
    }>> {
        return apiClient.get(`/students/${studentId}/performance`, filters);
    },
};
