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
    classroom_id?: number;
    stream_id?: number;
    category_id?: number;
    trip_id?: number | null;
    drop_off_point_id?: number | null;
    drop_off_point_other?: string | null;
    class_name?: string;
    stream_name?: string;
    status: 'active' | 'archived' | 'transferred' | 'graduated';
    category?: string;
    avatar?: string;
    phone?: string;
    email?: string;
    address?: string;
    residential_area?: string | null;
    preferred_hospital?: string | null;
    nemis_number?: string | null;
    knec_assessment_number?: string | null;
    religion?: string | null;
    has_allergies?: boolean;
    allergies_notes?: string | null;
    is_fully_immunized?: boolean | null;
    emergency_contact_name?: string | null;
    emergency_contact_phone?: string | null;
    emergency_contact_phone_local?: string | null;
    blood_group?: string;
    admission_date?: string;
    enrollment_year?: string | number | null;
    parent?: {
        father_name?: string | null;
        mother_name?: string | null;
        father_phone?: string | null;
        mother_phone?: string | null;
        father_email?: string | null;
        mother_email?: string | null;
        guardian_name?: string | null;
        guardian_phone?: string | null;
        guardian_email?: string | null;
        father_whatsapp?: string | null;
        mother_whatsapp?: string | null;
        guardian_whatsapp?: string | null;
        guardian_relationship?: string | null;
        marital_status?: string | null;
        father_id_number?: string | null;
        mother_id_number?: string | null;
        father_phone_country_code?: string | null;
        mother_phone_country_code?: string | null;
        guardian_phone_country_code?: string | null;
        father_phone_local?: string | null;
        mother_phone_local?: string | null;
        guardian_phone_local?: string | null;
        father_whatsapp_local?: string | null;
        mother_whatsapp_local?: string | null;
        guardian_whatsapp_local?: string | null;
    };

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
    full_name?: string;
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

/** Subject rows returned by GET /classes/{id}/subjects */
export interface ClassSubject {
    id: number;
    name: string;
    code?: string | null;
}
