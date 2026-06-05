import type {
  StaffDetail,
  StaffEmploymentStatus,
  StaffListFilters,
  StaffListQueryParams,
  StaffRecord,
  StaffSummary,
} from '../types/staff';

function normalizeEmploymentStatus(
  raw?: string | null,
): StaffEmploymentStatus | null {
  if (!raw) return null;
  const v = raw.toLowerCase().trim();
  if (v === 'active' || v === 'on_leave' || v === 'terminated' || v === 'suspended') {
    return v;
  }
  return null;
}

export function toStaffSummary(raw: StaffRecord): StaffSummary {
  return {
    id: raw.id,
    employeeNumber: raw.staff_id ?? raw.employee_number ?? '',
    fullName: raw.full_name?.trim() || `${raw.first_name ?? ''} ${raw.last_name ?? ''}`.trim(),
    departmentName: raw.department ?? null,
    jobTitle: raw.job_title ?? raw.designation ?? null,
    systemRole: raw.system_role ?? raw.role ?? null,
    staffCategory: raw.staff_category ?? null,
    employmentStatus: normalizeEmploymentStatus(raw.employment_status),
    gender: raw.gender ?? null,
    avatarUrl: raw.avatar ?? null,
    phone: raw.phone_number ?? raw.phone ?? null,
    email: raw.work_email ?? null,
  };
}

export function toStaffDetail(raw: StaffRecord): StaffDetail {
  const summary = toStaffSummary(raw);
  return {
    ...summary,
    idNumber: raw.id_number ?? null,
    personalEmail: raw.personal_email ?? null,
    maritalStatus: raw.marital_status ?? null,
    residentialAddress: raw.residential_address ?? null,
    emergencyContact: {
      name: raw.emergency_contact_name ?? null,
      relationship: raw.emergency_contact_relationship ?? null,
      phone: raw.emergency_contact_phone ?? null,
    },
    hireDate: raw.hire_date ?? null,
    terminationDate: raw.termination_date ?? null,
    employmentType: raw.employment_type ?? null,
    contractStartDate: raw.contract_start_date ?? null,
    contractEndDate: raw.contract_end_date ?? null,
    dateOfBirth: raw.date_of_birth ?? null,
    departmentId: raw.department_id ?? null,
    staffCategoryId: raw.staff_category_id ?? null,
    jobTitleId: raw.job_title_id ?? null,
    supervisorId: raw.supervisor_id ?? null,
    supervisorName: raw.supervisor_name ?? null,
    maxLessonsPerWeek: raw.max_lessons_per_week ?? null,
    basicSalary: raw.basic_salary ?? null,
    bankName: raw.bank_name ?? null,
    bankBranch: raw.bank_branch ?? null,
    bankAccount: raw.bank_account ?? null,
    kraPin: raw.kra_pin ?? null,
    nssf: raw.nssf ?? null,
    nhif: raw.nhif ?? null,
    statutoryExemptions: raw.statutory_exemptions ?? [],
  };
}

export function buildStaffQueryParams(
  filters: StaffListFilters,
  page: number,
): StaffListQueryParams {
  const params: StaffListQueryParams = {
    page,
    per_page: filters.perPage ?? 25,
  };
  if (filters.search) params.search = filters.search;
  if (filters.departmentId != null) params.department_id = filters.departmentId;
  if (filters.staffCategoryId != null) params.staff_category_id = filters.staffCategoryId;
  if (filters.employmentStatus && filters.employmentStatus !== 'all') {
    params.employment_status = filters.employmentStatus;
  }
  if (filters.gender && filters.gender !== 'all') {
    params.gender = filters.gender;
  }
  if (filters.role) params.role = filters.role;
  return params;
}
