/** Raw staff shape from `GET /staff` and `GET /staff/{id}`. */
export interface StaffRecord {
  id: number;
  staff_id?: string;
  employee_number?: string;
  first_name?: string;
  last_name?: string;
  middle_name?: string | null;
  full_name: string;
  work_email?: string | null;
  personal_email?: string | null;
  phone?: string | null;
  phone_number?: string | null;
  designation?: string | null;
  job_title?: string | null;
  role?: string | null;
  system_role?: string | null;
  department?: string | null;
  department_id?: number | null;
  staff_category_id?: number | null;
  staff_category?: string | null;
  employment_status?: StaffEmploymentStatus | string | null;
  gender?: string | null;
  status?: string;
  avatar?: string | null;
  id_number?: string | null;
  marital_status?: string | null;
  residential_address?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_relationship?: string | null;
  emergency_contact_phone?: string | null;
  bank_name?: string | null;
  bank_branch?: string | null;
  bank_account?: string | null;
  kra_pin?: string | null;
  nssf?: string | null;
  nhif?: string | null;
  statutory_exemptions?: string[];
  basic_salary?: number | null;
  date_of_birth?: string | null;
  hire_date?: string | null;
  termination_date?: string | null;
  employment_type?: string | null;
  contract_start_date?: string | null;
  contract_end_date?: string | null;
  max_lessons_per_week?: number | null;
  job_title_id?: number | null;
  supervisor_id?: number | null;
  supervisor_name?: string | null;
  created_at?: string;
  updated_at?: string;
}

export type StaffEmploymentStatus = 'active' | 'on_leave' | 'terminated' | 'suspended';

export type StaffGenderFilter = 'all' | 'male' | 'female' | 'other';

export type StaffEmploymentStatusFilter = 'all' | StaffEmploymentStatus;

/** List row model (registry). */
export interface StaffSummary {
  id: number;
  employeeNumber: string;
  fullName: string;
  departmentName: string | null;
  jobTitle: string | null;
  systemRole: string | null;
  staffCategory: string | null;
  employmentStatus: StaffEmploymentStatus | null;
  gender: string | null;
  avatarUrl: string | null;
  phone: string | null;
  email: string | null;
}

/** Detail model (routing + overview; full 360 in later sprints). */
export interface StaffDetail extends StaffSummary {
  idNumber: string | null;
  personalEmail: string | null;
  maritalStatus: string | null;
  residentialAddress: string | null;
  emergencyContact: {
    name: string | null;
    relationship: string | null;
    phone: string | null;
  };
  hireDate: string | null;
  terminationDate: string | null;
  employmentType: string | null;
  contractStartDate: string | null;
  contractEndDate: string | null;
  dateOfBirth: string | null;
  departmentId: number | null;
  staffCategoryId: number | null;
  jobTitleId: number | null;
  supervisorId: number | null;
  supervisorName: string | null;
  maxLessonsPerWeek: number | null;
  basicSalary: number | null;
  bankName: string | null;
  bankBranch: string | null;
  bankAccount: string | null;
  kraPin: string | null;
  nssf: string | null;
  nhif: string | null;
  statutoryExemptions: string[];
}

export interface StaffListQueryParams {
  search?: string;
  department_id?: number;
  staff_category_id?: number;
  employment_status?: string;
  gender?: string;
  role?: string;
  page?: number;
  per_page?: number;
}

export interface StaffListFilters {
  search?: string;
  departmentId: number | null;
  staffCategoryId: number | null;
  employmentStatus: StaffEmploymentStatusFilter;
  gender: StaffGenderFilter;
  role: string | null;
  perPage?: number;
}

export interface StaffFilterOption {
  id: number;
  name: string;
}

export interface StaffFilterEnumOption {
  value: string;
  label: string;
}

export interface StaffFilterOptions {
  departments: StaffFilterOption[];
  categories: StaffFilterOption[];
  roles: string[];
  employmentStatuses: StaffFilterEnumOption[];
  genders: StaffFilterEnumOption[];
}
