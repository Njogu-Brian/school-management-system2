export interface AttendanceRecord {
    id: number;
    student_id: number;
    date: string;
    status: 'present' | 'absent' | 'late' | 'excused';
    reason?: string;
    marked_by?: number;
    marked_at?: string;

    // Populated fields
    student?: {
        id: number;
        full_name: string;
        admission_number: string;
        avatar?: string;
    };
}

export interface MarkAttendanceData {
    date: string;
    class_id: number;
    stream_id?: number;
    records: {
        student_id: number;
        status: 'present' | 'absent' | 'late' | 'excused';
        reason?: string;
    }[];
}

export interface AttendanceFilters {
    student_id?: number;
    class_id?: number;
    stream_id?: number;
    date_from?: string;
    date_to?: string;
    status?: string;
}

export interface AttendanceStats {
    total_days: number;
    present_days: number;
    absent_days: number;
    late_days: number;
    excused_days: number;
    attendance_percentage: number;
}

export interface AttendanceAnalytics {
    at_risk_students: {
        student: {
            id: number;
            full_name: string;
            admission_number: string;
            class_name: string;
        };
        attendance_percentage: number;
        absent_days: number;
    }[];

    consecutive_absences: {
        student: {
            id: number;
            full_name: string;
            admission_number: string;
            class_name: string;
        };
        consecutive_days: number;
        last_present_date: string;
    }[];
}
