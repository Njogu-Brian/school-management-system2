export type Student360TabId =
  | 'overview'
  | 'attendance'
  | 'academics'
  | 'fees'
  | 'family'
  | 'health'
  | 'transport'
  | 'requirements'
  | 'documents';

export interface Student360HeaderData {
  fullName: string;
  admissionNumber: string;
  classLabel: string;
  avatarUrl?: string | null;
  enrollmentStatus?: string;
  feeStatus?: 'cleared' | 'pending' | null;
}

export interface StudentSummaryWidgetData {
  id: string;
  label: string;
  value: string;
  delta?: string;
  icon?: string;
}

export interface StudentTimelineEventData {
  id: string;
  title: string;
  subtitle?: string;
  occurredAtLabel: string;
  kind: 'payment' | 'invoice' | 'enrollment' | 'update' | 'other';
}
