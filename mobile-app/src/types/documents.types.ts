export interface Document {
    id: number;
    title: string;
    description?: string;
    category: string;
    file_name: string;
    file_path: string;
    file_type: string;
    file_size: number;
    uploaded_by: number;
    uploaded_by_name?: string;
    visibility: 'public' | 'staff_only' | 'students_only' | 'parents_only' | 'private';
    tags?: string[];
    download_count?: number;
    created_at: string;
    updated_at: string;
}

export interface DocumentTemplate {
    id: number;
    name: string;
    type: 'pdf' | 'docx' | 'xlsx';
    category: string;
    file_path: string;
    variables?: string[];
    description?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface ReportCard {
    id: number;
    student_id: number;
    student_name?: string;
    class_id: number;
    class_name?: string;
    term_id: number;
    academic_year_id: number;
    file_path?: string;
    status: 'draft' | 'generated' | 'published';
    generated_at?: string;
    published_at?: string;
    created_at: string;
    updated_at: string;
}

export interface Certificate {
    id: number;
    student_id: number;
    student_name?: string;
    type: 'completion' | 'transfer' | 'bonafide' | 'character' | 'other';
    issue_date: string;
    certificate_number: string;
    file_path?: string;
    issued_by: number;
    issued_by_name?: string;
    created_at: string;
    updated_at: string;
}

export interface DocumentFilters {
    search?: string;
    category?: string;
    visibility?: string;
    file_type?: string;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
}
