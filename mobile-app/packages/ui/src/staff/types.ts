export type StaffEmploymentStatusUi =
  | 'active'
  | 'on_leave'
  | 'terminated'
  | 'suspended';

export interface StaffListItemData {
  id: number;
  fullName: string;
  employeeNumber: string;
  departmentName: string | null;
  jobTitle: string | null;
  systemRole: string | null;
  employmentStatus: StaffEmploymentStatusUi | null;
  avatarUrl: string | null;
  onPress?: () => void;
}

export type StaffEmploymentStatusFilterUi = 'all' | StaffEmploymentStatusUi;
export type StaffGenderFilterUi = 'all' | 'male' | 'female' | 'other';
