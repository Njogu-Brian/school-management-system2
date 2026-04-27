export type AcademicReportQuestionType =
    | 'short_text'
    | 'long_text'
    | 'single_select'
    | 'multi_select'
    | 'file_upload';

export interface AcademicReportListItem {
    id: number;
    title: string;
    description?: string | null;
    status: string;
    open_from?: string | null;
    open_until?: string | null;
    questions_count?: number;
}

export interface AcademicReportQuestion {
    id: number;
    template_id: number;
    type: AcademicReportQuestionType;
    label: string;
    help_text?: string | null;
    is_required: boolean;
    options?: any;
    display_order: number;
}

export interface AcademicReportTemplate {
    id: number;
    title: string;
    description?: string | null;
    status: string;
    open_from?: string | null;
    open_until?: string | null;
    questions: AcademicReportQuestion[];
}

export type AcademicReportAnswerInput = {
    question_id: number;
    value_text?: string | null;
    value_json?: any;
};

