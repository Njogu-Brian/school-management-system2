export interface ExamType {
    id: number;
    name: string;
    code?: string;
    description?: string;
    is_active: boolean;
}

export interface Exam {
    id: number;
    name: string;
    exam_type_id: number;
    exam_type_name?: string;
    academic_year_id: number;
    term_id: number;
    start_date: string;
    end_date: string;
    status: 'draft' | 'published' | 'ongoing' | 'completed';
    total_marks?: number;
    classroom_id?: number | null;
    stream_id?: number | null;
    subject_id?: number | null;
    classroom_name?: string | null;
    subject_name?: string | null;
    created_at: string;
    updated_at: string;
}

export interface ExamSchedule {
    id: number;
    exam_id: number;
    subject_id: number;
    subject_name?: string;
    date: string;
    start_time: string;
    end_time: string;
    duration_minutes: number;
    room?: string;
    invigilator_id?: number;
    invigilator_name?: string;
}

export interface Mark {
    id: number;
    exam_id: number;
    student_id: number;
    student_name?: string;
    student_admission_number?: string;
    subject_id: number;
    subject_name?: string;
    marks: number;
    total_marks: number;
    percentage: number;
    grade?: string;
    remarks?: string;
    entered_by: number;
    entered_by_name?: string;
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
    exam_id?: number;
    overall_marks: number;
    overall_percentage: number;
    overall_grade?: string;
    overall_position?: number;
    class_position?: number;
    stream_position?: number;
    subjects: ReportCardSubject[];
    skills?: SkillAssessment[];
    teacher_comment?: string;
    principal_comment?: string;
    status: 'draft' | 'published';
    generated_at?: string;
    created_at: string;
    updated_at: string;
}

export interface ReportCardSubject {
    subject_id: number;
    subject_name: string;
    marks: number;
    total_marks: number;
    percentage: number;
    grade: string;
    remarks?: string;
    position?: number;
}

export interface SkillAssessment {
    skill_name: string;
    rating: 'excellent' | 'good' | 'average' | 'needs_improvement';
    comment?: string;
}

export interface Subject {
    id: number;
    name: string;
    code?: string;
    category?: string;
    is_compulsory: boolean;
    is_active: boolean;
}

export interface Timetable {
    id: number;
    class_id?: number;
    class_name?: string;
    teacher_id?: number;
    teacher_name?: string;
    term_id: number;
    academic_year_id: number;
    slots: TimetableSlot[];
}

export interface TimetableSlot {
    id: number;
    day: 'Monday' | 'Tuesday' | 'Wednesday' | 'Thursday' | 'Friday';
    start_time: string;
    end_time: string;
    subject_id: number;
    subject_name?: string;
    teacher_id?: number;
    teacher_name?: string;
    room?: string;
}

export interface Assignment {
    id: number;
    title: string;
    description: string;
    subject_id: number;
    subject_name?: string;
    class_id: number;
    class_name?: string;
    teacher_id: number;
    teacher_name?: string;
    due_date: string;
    total_marks: number;
    attachments?: string[];
    status: 'active' | 'closed';
    created_at: string;
    updated_at: string;
}

export interface AssignmentSubmission {
    id: number;
    assignment_id: number;
    student_id: number;
    student_name?: string;
    submitted_at?: string;
    grade?: number;
    feedback?: string;
    attachments?: string[];
    status: 'pending' | 'submitted' | 'graded' | 'late';
}

export interface LessonPlan {
    id: number;
    teacher_id: number;
    teacher_name?: string | null;
    subject_id: number;
    subject_name?: string;
    class_id: number;
    class_name?: string;
    topic: string;
    objectives: string[];
    activities: string[];
    resources: string[];
    assessment_methods: string[];
    date: string;
    duration_minutes: number;
    status: 'draft' | 'approved' | 'completed';
    created_at: string;
    updated_at: string;
}

export interface AcademicsFilters {
    exam_id?: number;
    class_id?: number;
    classroom_id?: number;
    subject_id?: number;
    student_id?: number;
    teacher_id?: number;
    term_id?: number;
    academic_year_id?: number;
    status?: string;
    date_from?: string;
    date_to?: string;
    page?: number;
    per_page?: number;
}

export interface MarksMatrixContext {
    exam_types: { id: number; name: string; code?: string | null }[];
    classrooms: { id: number; name: string }[];
    streams: { id: number; name: string; classroom_id: number }[];
}

export interface MarksMatrixExam {
    id: number;
    name: string;
    subject_id: number;
    subject_name?: string | null;
    min_marks: number;
    max_marks: number;
}

export interface MarksMatrixStudent {
    id: number;
    full_name: string;
    admission_number?: string | null;
    classroom_id: number;
    stream_id?: number | null;
}

export interface MarksMatrixExistingMark {
    student_id: number;
    exam_id: number;
    marks?: number | null;
    remarks?: string | null;
}
