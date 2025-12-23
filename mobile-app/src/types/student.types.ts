export interface Student {
    id: number;
    admission_number: string;
    first_name: string;
    last_name: string;
    middle_name?: string;
    full_name: string;
    date_of_birth: string;
    gender: 'male' | 'female' | 'other';
    class_id: number;
    stream_id?: number;
    class_name?: string;
    stream_name?: string;
    status: 'active' | 'archived' | 'transferred' | 'graduated';
    category?: string;
    avatar?: string;
    phone?: string;
    email?: string;
    address?: string;

    // Guardians
    guardians?: Guardian[];

    // Stats (optional, populated on detail)
    attendance_percentage?: number;
    fees_balance?: number;
    exam_average?: number;

    created_at: string;
    updated_at: string;
}

export interface Guardian {
    id: number;
    name: string;
    relationship: string;
    phone: string;
    email?: string;
    is_primary: boolean;
}

export interface StudentFilters {
    search?: string;
    class_id?: number;
    stream_id?: number;
    status?: string;
    gender?: string;
    category?: string;
    page?: number;
    per_page?: number;
}

export interface CreateStudentData {
    // Personal Info
    first_name: string;
    last_name: string;
    middle_name?: string;
    date_of_birth: string;
    gender: string;
    admission_number?: string;

    // Class Assignment
    class_id: number;
    stream_id?: number;

    // Contact
    phone?: string;
    email?: string;
    address?: string;

    // Category
    category?: string;

    // Guardian (can be added separately)
    guardian_name?: string;
    guardian_phone?: string;
    guardian_email?: string;
    guardian_relationship?: string;
}

export interface UpdateStudentData extends Partial<CreateStudentData> {
    status?: string;
}

export interface Class {
    id: number;
    name: string;
    level?: number;
    code?: string;
}

export interface Stream {
    id: number;
    name: string;
    class_id: number;
}
