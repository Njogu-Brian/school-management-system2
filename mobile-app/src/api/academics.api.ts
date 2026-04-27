import { apiClient } from './client';
import {
    Exam,
    Mark,
    ReportCard,
    Timetable,
    Assignment,
    LessonPlan,
    AcademicsFilters,
    MarksMatrixContext,
    MarksMatrixExam,
    MarksMatrixStudent,
    MarksMatrixExistingMark,
} from '../types/academics.types';
import { ApiResponse, PaginatedResponse } from '../types/api.types';

/** Mobile API client aligned with `routes/api.php` (sanctum). */
export const academicsApi = {
    async getExams(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<Exam>>> {
        return apiClient.get<PaginatedResponse<Exam>>('/exams', filters);
    },

    async getExam(id: number): Promise<ApiResponse<Exam>> {
        return apiClient.get<Exam>(`/exams/${id}`);
    },

    async getExamMarkingOptions(examId: number): Promise<
        ApiResponse<{ classroom_id: number; classroom_name: string; subject_id: number; subject_name: string }[]>
    > {
        return apiClient.get(`/exams/${examId}/marking-options`);
    },

    async getMarks(filters: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<Mark>>> {
        return apiClient.get<PaginatedResponse<Mark>>('/marks', filters);
    },

    async enterMarks(data: {
        exam_id: number;
        subject_id: number;
        classroom_id: number;
        marks: { student_id: number; marks: number; remarks?: string }[];
    }): Promise<ApiResponse<{ count: number; message: string }>> {
        return apiClient.post('/exam-marks/batch', data);
    },

    async getMarksMatrixContext(classroomId?: number): Promise<ApiResponse<MarksMatrixContext>> {
        return apiClient.get<MarksMatrixContext>('/marks/matrix/context', classroomId ? { classroom_id: classroomId } : undefined);
    },

    async getMarksMatrix(filters: {
        exam_type_id: number;
        classroom_id: number;
        stream_id?: number;
    }): Promise<ApiResponse<{ students: MarksMatrixStudent[]; exams: MarksMatrixExam[]; existing_marks: MarksMatrixExistingMark[] }>> {
        return apiClient.get('/marks/matrix', filters);
    },

    async enterMarksMatrix(data: {
        exam_type_id: number;
        classroom_id: number;
        stream_id?: number;
        entries: { student_id: number; exam_id: number; marks?: number; remarks?: string }[];
    }): Promise<ApiResponse<{ count: number; skipped: number; message: string }>> {
        return apiClient.post('/exam-marks/matrix/batch', data);
    },

    async getReportCards(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<ReportCard>>> {
        return apiClient.get<PaginatedResponse<ReportCard>>('/report-cards', filters);
    },

    async getReportCard(id: number): Promise<ApiResponse<ReportCard>> {
        return apiClient.get<ReportCard>(`/report-cards/${id}`);
    },

    async getTeacherTimetable(teacherId: number, termId: number): Promise<ApiResponse<Timetable>> {
        return apiClient.get<Timetable>(`/timetables/teacher/${teacherId}`, { term_id: termId });
    },

    async getStudentTimetable(studentId: number, termId: number): Promise<ApiResponse<Timetable>> {
        return apiClient.get<Timetable>(`/timetables/student/${studentId}`, { term_id: termId });
    },

    async getAssignments(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<Assignment>>> {
        return apiClient.get<PaginatedResponse<Assignment>>('/assignments', filters);
    },

    async createAssignment(data: {
        title: string;
        instructions?: string;
        due_date: string;
        classroom_id: number;
        subject_id: number;
        stream_id?: number | null;
        max_score?: number | null;
    }): Promise<ApiResponse<Assignment>> {
        return apiClient.post<Assignment>('/assignments', data);
    },

    async getAssignment(id: number): Promise<ApiResponse<Assignment>> {
        return apiClient.get<Assignment>(`/assignments/${id}`);
    },

    async getLessonPlans(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<LessonPlan>>> {
        return apiClient.get<PaginatedResponse<LessonPlan>>('/lesson-plans', filters);
    },

    async getLessonPlan(id: number): Promise<ApiResponse<LessonPlan>> {
        return apiClient.get<LessonPlan>(`/lesson-plans/${id}`);
    },

    async createLessonPlan(data: {
        timetable_id?: number | null;
        planned_date: string;
        title: string;
        duration_minutes?: number | null;
        learning_objectives?: string[] | null;
        learning_resources?: string[] | null;
        activities?: string[] | null;
        substrand_id?: number | null;
        lesson_number?: string | null;
        // Fallback when no timetable_id is used
        classroom_id?: number | null;
        subject_id?: number | null;
        term_id?: number | null;
        academic_year_id?: number | null;
    }): Promise<ApiResponse<LessonPlan>> {
        return apiClient.post<LessonPlan>('/lesson-plans', data);
    },

    async updateLessonPlan(
        id: number,
        data: {
            timetable_id?: number | null;
            planned_date: string;
            title: string;
            duration_minutes?: number | null;
            learning_objectives?: string[] | null;
            learning_resources?: string[] | null;
            activities?: string[] | null;
            substrand_id?: number | null;
            lesson_number?: string | null;
        }
    ): Promise<ApiResponse<LessonPlan>> {
        return apiClient.put<LessonPlan>(`/lesson-plans/${id}`, data);
    },

    async submitLessonPlan(id: number): Promise<ApiResponse<LessonPlan>> {
        return apiClient.post<LessonPlan>(`/lesson-plans/${id}/submit`);
    },

    async getLessonPlansReviewQueue(filters?: AcademicsFilters): Promise<ApiResponse<PaginatedResponse<LessonPlan>>> {
        return apiClient.get<PaginatedResponse<LessonPlan>>('/lesson-plans/review-queue', filters);
    },

    async approveLessonPlan(id: number, approval_notes?: string): Promise<ApiResponse<LessonPlan>> {
        return apiClient.post<LessonPlan>(`/lesson-plans/${id}/approve`, { approval_notes });
    },

    async rejectLessonPlan(id: number, rejection_notes: string): Promise<ApiResponse<LessonPlan>> {
        return apiClient.post<LessonPlan>(`/lesson-plans/${id}/reject`, { rejection_notes });
    },
};
