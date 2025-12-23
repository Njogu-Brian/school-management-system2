// User roles in the system
export enum UserRole {
    SUPER_ADMIN = 'super_admin',
    ADMIN = 'admin',
    SECRETARY = 'secretary',
    TEACHER = 'teacher',
    SUPERVISOR = 'supervisor',
    ACCOUNTANT = 'accountant',
    FINANCE = 'finance',
    PARENT = 'parent',
    GUARDIAN = 'guardian',
    STUDENT = 'student',
    DRIVER = 'driver',
    TRANSPORT = 'transport',
}

// Permission types
export const PERMISSIONS = {
    // Students
    VIEW_STUDENTS: 'view_students',
    CREATE_STUDENTS: 'create_students',
    EDIT_STUDENTS: 'edit_students',
    DELETE_STUDENTS: 'delete_students',

    // Attendance
    MARK_ATTENDANCE: 'mark_attendance',
    VIEW_ATTENDANCE: 'view_attendance',
    EDIT_ATTENDANCE: 'edit_attendance',

    // Finance
    VIEW_INVOICES: 'view_invoices',
    CREATE_INVOICES: 'create_invoices',
    VIEW_PAYMENTS: 'view_payments',
    CREATE_PAYMENTS: 'create_payments',

    // Academics
    VIEW_EXAMS: 'view_exams',
    CREATE_EXAMS: 'create_exams',
    ENTER_MARKS: 'enter_marks',
    PUBLISH_RESULTS: 'publish_results',

    // HR
    VIEW_STAFF: 'view_staff',
    MANAGE_STAFF: 'manage_staff',
    APPROVE_LEAVE: 'approve_leave',
    PROCESS_PAYROLL: 'process_payroll',

    // System
    VIEW_LOGS: 'view_logs',
    MANAGE_SETTINGS: 'manage_settings',
    BACKUP_RESTORE: 'backup_restore',
} as const;

export type Permission = typeof PERMISSIONS[keyof typeof PERMISSIONS];
