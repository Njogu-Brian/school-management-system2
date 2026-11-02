export type ApplicationStatusFilter =
  | 'all'
  | 'pending'
  | 'contacted'
  | 'under_review'
  | 'interviewed'
  | 'ready_to_enroll'
  | 'waitlisted'
  | 'enrolled'
  | 'rejected';

export interface ApplicationListItemData {
  id: number;
  fullName: string;
  applicationStatus: ApplicationStatusFilter | string;
  applicationDate: string | null;
  preferredClassName: string | null;
  className: string | null;
  waitlistPosition: number | null;
  avatarUrl: string | null;
}
