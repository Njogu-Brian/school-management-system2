export interface Staff {
    id: number;
    employee_number: string;
    first_name: string;
    last_name: string;
    middle_name?: string;
    full_name: string;
    email: string;
    phone: string;
    gender: 'male' | 'female' | 'other';
    date_of_birth: string;
    id_number?: string;
    role: string;
    department?: string;
    designation?: string;
    employment_type: 'full_time' | 'part_time' | 'contract' | 'intern';
    employment_date: string;
    status: 'active' | 'on_leave' | 'suspended' | 'terminated';
    avatar?: string;
    address?: string;
    emergency_contact_name?: string;
    emergency_contact_phone?: string;

    // Payroll info (optional)
    basic_salary?: number;
    bank_name?: string;
    bank_account?: string;

    created_at: string;
    updated_at: string;
}

export interface Leave {
    id: number;
    staff_id: number;
    staff_name?: string;
    leave_type: 'annual' | 'sick' | 'maternity' | 'paternity' | 'unpaid' | 'other';
    start_date: string;
    end_date: string;
    days: number;
    reason: string;
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
}

export interface LeaveFilters {
    staff_id?: number;
    leave_type?: string;
    status?: string;
    date_from?: string;
    date_to?: string;
}
