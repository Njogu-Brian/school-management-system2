/** Presentational approval types (mirror `@erp/core` domain). */
export type ApprovalStatus =
  | 'pending'
  | 'approved'
  | 'rejected'
  | 'escalated'
  | 'expired';

export type ApprovalPriority = 'critical' | 'high' | 'medium' | 'low';

export type ApprovalSourceType = 'leave_request' | 'lesson_plan' | 'online_admission';

export interface ApprovalCardData {
  id: string;
  title: string;
  subtitle: string;
  status: ApprovalStatus;
  priority: ApprovalPriority;
  sourceLabel?: string;
  requestedAtLabel?: string;
  onPress?: () => void;
}
