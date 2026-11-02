export type Admissions360TabId =
  | 'overview'
  | 'student'
  | 'parents'
  | 'interview'
  | 'documents'
  | 'timeline'
  | 'enrollment';

export interface Admissions360HeaderData {
  id: number;
  fullName: string;
  applicationStatus: string;
  applicationDate: string | null;
  preferredClassName: string | null;
  avatarUrl: string | null;
  waitlistPosition: number | null;
}
