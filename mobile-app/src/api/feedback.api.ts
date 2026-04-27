import { apiClient } from './client';
import type { AcademicReportTemplate, AcademicReportAnswerInput } from '@types/academicReports.types';

export const feedbackApi = {
    getTemplate: async (): Promise<AcademicReportTemplate> => {
        const res = await apiClient.get<AcademicReportTemplate>('/feedback/template');
        return (res as any)?.data;
    },

    submit: async (payload: { is_anonymous?: boolean; answers: AcademicReportAnswerInput[] }): Promise<{ id: number }> => {
        const res = await apiClient.post<{ id: number }>('/feedback/submit', payload);
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
            `/feedback/submissions/${params.submissionId}/questions/${params.questionId}/file`,
            form
        );
        return (res as any)?.data;
    },
};

