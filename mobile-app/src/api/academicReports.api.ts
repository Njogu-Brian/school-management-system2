import { apiClient } from './client';
import type {
    AcademicReportListItem,
    AcademicReportTemplate,
    AcademicReportAnswerInput,
} from '@types/academicReports.types';

export const academicReportsApi = {
    getAssigned: async (): Promise<AcademicReportListItem[]> => {
        const res = await apiClient.get<AcademicReportListItem[]>('/academic-reports/assigned');
        return (res as any)?.data ?? [];
    },

    getTemplate: async (templateId: number): Promise<AcademicReportTemplate> => {
        const res = await apiClient.get<AcademicReportTemplate>(`/academic-reports/templates/${templateId}`);
        return (res as any)?.data;
    },

    submit: async (payload: {
        template_id: number;
        is_anonymous?: boolean;
        submitted_for?: any;
        answers: AcademicReportAnswerInput[];
    }): Promise<{ id: number }> => {
        const res = await apiClient.post<{ id: number }>('/academic-reports/submissions', payload);
        return (res as any)?.data;
    },

    uploadFileAnswer: async (params: {
        submissionId: number;
        questionId: number;
        file: { uri: string; name: string; type: string };
    }): Promise<{ id: number; file_path: string }> => {
        const form = new FormData();
        form.append('file', params.file as any);
        const res = await apiClient.upload<{ id: number; file_path: string }>(
            `/academic-reports/submissions/${params.submissionId}/questions/${params.questionId}/file`,
            form
        );
        return (res as any)?.data;
    },
};

