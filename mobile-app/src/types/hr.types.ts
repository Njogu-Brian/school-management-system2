export interface Staff {
    id: number;
    employee_number: string;
    first_name: string;
    last_name: string;
    middle_name?: string;
    full_name: string;
    /** Legacy / optional; API uses work_email */
    email?: string;
    work_email?: string;
    phone?: string;
    phone_number?: string;
    gender?: string;
    date_of_birth?: string;
    id_number?: string;
    marital_status?: string;
    role?: string;
    department?: string;
    designation?: string;
    job_title?: string;
    employment_type?: string;
    employment_status?: string;
    employment_date?: string;
    hire_date?: string;
    termination_date?: string;
    contract_start_date?: string;
    contract_end_date?: string;
    max_lessons_per_week?: number | null;
    staff_category?: string;
    staff_category_id?: number | null;
    supervisor_id?: number | null;
    supervisor_name?: string | null;
    status?: 'active' | 'on_leave' | 'suspended' | 'terminated' | 'archived';
    avatar?: string;
    address?: string;
    emergency_contact_name?: string;
    emergency_contact_relationship?: string;
    emergency_contact_phone?: string;
    kra_pin?: string;
    nssf?: string;
    nhif?: string;
    /** Codes exempted from statutory deductions (e.g. PAYE, NSSF). */
    statutory_exemptions?: string[];

    // Payroll info (optional)
    basic_salary?: number;
    bank_name?: string;
    bank_branch?: string;
    bank_account?: string;
    residential_address?: string;
    personal_email?: string;
    department_id?: number | null;
    job_title_id?: number | null;

    created_at: string;
    updated_at: string;
}

export interface Leave {
    id: number;
    staff_id: number;
    staff_name?: string;
    leave_type?: string;
    leave_type_name?: string;
    leave_type_id?: number;
    start_date: string;
    end_date: string;
    days: number;
    days_count?: number;
    reason?: string | null;
    status: 'pending' | 'approved' | 'rejected' | 'cancelled';
    approved_by?: number;
    approved_at?: string;
    rejection_reason?: string;
    created_at: string;
    updated_at: string;
}

/** Alias for Leave (used by LeaveManagementScreen) */
export type LeaveApplication = Leave;

export interface StaffAttendance {
    id: number;
    staff_id: number;
    staff_name?: string;
    date: string;
    check_in?: string;
    check_out?: string;
    hours_worked?: number;
    status: 'present' | 'absent' | 'late' | 'half_day' | 'on_leave';
    notes?: string;
}

export interface Payroll {
    id: number;
    staff_id: number;
    staff_name?: string;
    staff_employee_number?: string;
    month: string;
    basic_salary: number;
    allowances: number;
    deductions: number;
    gross_salary: number;
    net_salary: number;
    status: 'draft' | 'processed' | 'paid';
    payment_date?: string;
    created_at: string;
    updated_at: string;
    items?: PayrollItem[];
}

export interface PayrollItem {
    id: number;
    payroll_id: number;
    type: 'allowance' | 'deduction';
    name: string;
    amount: number;
}

export interface SalaryAdvance {
    id: number;
    staff_id: number;
    staff_name?: string;
    amount: number;
    reason: string;
    request_date: string;
    status: 'pending' | 'approved' | 'rejected' | 'paid' | 'recovered';
    approved_by?: number;
    approved_at?: string;
    recovery_months?: number;
    monthly_deduction?: number;
    balance?: number;
}

export interface StaffFilters {
    search?: string;
    role?: string;
    department?: string;
    employment_type?: string;
    status?: string;
    page?: number;
    per_page?: number;
}

export interface CreateStaffData {
    first_name: string;
    last_name: string;
    middle_name?: string;
    email: string;
    phone: string;
    gender: string;
    date_of_birth: string;
    id_number?: string;
    role: string;
    department?: string;
    designation?: string;
    employment_type: string;
    employment_date: string;
    basic_salary?: number;
    address?: string;
    emergency_contact_name?: string;
    emergency_contact_phone?: string;
}

export interface UpdateStaffData extends Partial<CreateStaffData> {
    status?: string;
    /** API uses work_email */
    work_email?: string;
    phone_number?: string;
    id_number?: string;
    personal_email?: string;
    residential_address?: string;
    emergency_contact_name?: string;
    emergency_contact_relationship?: string;
    emergency_contact_phone?: string;
    bank_name?: string;
    bank_branch?: string;
    bank_account?: string;
    kra_pin?: string;
    nssf?: string;
    nhif?: string;
    basic_salary?: number;
    department_id?: number | null;
    job_title_id?: number | null;
    staff_category_id?: number | null;
    supervisor_id?: number | null;
    date_of_birth?: string;
    gender?: string;
}

export interface LeaveFilters {
    staff_id?: number;
    leave_type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
}
