export type Staff360TabId =
  | 'overview'
  | 'employment'
  | 'teaching'
  | 'leave'
  | 'attendance'
  | 'payroll'
  | 'performance'
  | 'documents'
  | 'training';

export interface Staff360HeaderData {
  fullName: string;
  employeeNumber: string;
  orgLabel: string;
  avatarUrl?: string | null;
  employmentStatus?: string | null;
  systemRole?: string | null;
}
